<?php
declare(strict_types=1);

namespace Nexus\Mail;

use InvalidArgumentException;
use Nexus\Auth\Auth;
use Nexus\Auth\Rbac\RoleManager;
use Nexus\Auth\UserRepository;
use Nexus\Core\Env;
use Nexus\Core\Request;
use Nexus\Core\Response;
use Nexus\Database\Database;
use Nexus\Events\AuditLogger;
use Nexus\Security\Csrf;
use Nexus\Security\Headers;
use Nexus\Security\Session;
use Nexus\View\View;
use RuntimeException;

/**
 * Dreambox Nexus Webmail Controller
 *
 * Enforces mail.access permission, first-visit @MAIL_DOMAIN onboarding,
 * real-time antivirus scanning, and controlled ephemeral reader jails.
 */
class MailController
{
    private Auth $auth;
    private UserRepository $userRepo;
    private RoleManager $roleMgr;
    private MailRepository $mailRepo;
    private MailScanner $scanner;
    private MailJail $jail;
    private ?Database $db;
    private View $view;
    private Csrf $csrf;
    private ?Session $session;
    private ?Mailer $mailer;

    public function __construct(
        Auth $auth,
        UserRepository $userRepo,
        RoleManager $roleMgr,
        MailRepository $mailRepo,
        MailScanner $scanner,
        MailJail $jail,
        ?Database $db,
        View $view,
        Csrf $csrf,
        ?Session $session = null,
        ?Mailer $mailer = null
    ) {
        $this->auth = $auth;
        $this->userRepo = $userRepo;
        $this->roleMgr = $roleMgr;
        $this->mailRepo = $mailRepo;
        $this->scanner = $scanner;
        $this->jail = $jail;
        $this->db = $db;
        $this->view = $view;
        $this->csrf = $csrf;
        $this->session = $session;
        $this->mailer = $mailer;
    }

    public function index(Request $request): Response
    {
        $gate = $this->authorizeMail($request);
        if ($gate['response'] !== null) {
            return $gate['response'];
        }
        $user = $gate['user'];

        if ($this->session !== null) {
            $this->session->start();
        }
        $csrfToken = $this->csrf->generate($this->session?->getId());
        $appBase = Request::getAppBase();
        $nonce = Headers::getNonce();
        $mailDomain = MailRepository::getMailDomain();
        $mailbox = $this->mailRepo->getMailboxForUser((string)$user['id']);

        if ($mailbox === null) {
            if ($request->wantsJson()) {
                return Response::json([
                    'needs_claim' => true,
                    'mail_domain' => $mailDomain,
                    'mailbox' => null,
                ], 200, 'Mailbox address claim required on first visit.');
            }

            $suggestedLocal = MailRepository::normalizeLocalPart((string)($user['username'] ?: $user['email']));
            if (!MailRepository::isValidLocalPart($suggestedLocal)) {
                $suggestedLocal = 'user';
            }

            $html = $this->view->render('mail.index', [
                'appBase' => $appBase,
                'nonce' => $nonce,
                'csrfToken' => $csrfToken,
                'currentUser' => $user,
                'user' => $user,
                'hasMailAccess' => true,
                'needsClaim' => true,
                'mailDomain' => $mailDomain,
                'suggestedLocal' => $suggestedLocal,
                'mailbox' => null,
                'folder' => 'inbox',
                'search' => '',
                'messages' => [],
                'counts' => ['inbox_unread' => 0, 'inbox' => 0, 'starred' => 0, 'sent' => 0, 'drafts' => 0, 'trash' => 0],
                'activeJail' => null,
                'activeMessage' => null,
                'error' => $this->session ? $this->session->getFlash('mail_error') : null,
                'success' => $this->session ? $this->session->getFlash('mail_success') : null,
            ]);

            return Response::html($html);
        }

        $folder = strtolower($request->getString('folder', 'inbox'));
        if (!in_array($folder, MailRepository::VALID_FOLDERS, true)) {
            $folder = 'inbox';
        }
        $search = trim($request->getString('q', ''));
        $messages = $this->mailRepo->getMessages((string)$user['id'], $folder, $search);
        $counts = $this->mailRepo->getFolderCounts((string)$user['id']);

        $activeMessage = null;
        $activeJail = null;
        $readId = trim($request->getString('read', ''));
        if ($readId !== '') {
            $candidate = $this->mailRepo->getMessage((string)$user['id'], $readId);
            if ($candidate !== null) {
                if (empty($candidate['is_read'])) {
                    $candidate = $this->mailRepo->updateMessage((string)$user['id'], $readId, ['is_read' => true]) ?? $candidate;
                }
                $this->jail->destroyAllForUser((string)$user['id']);
                $scan = $this->scanner->scan(
                    (string)($candidate['subject'] ?? ''),
                    (string)($candidate['body_text'] ?? ''),
                    (string)($candidate['body_html'] ?? ''),
                    (array)($candidate['attachments'] ?? [])
                );
                $activeJail = $this->jail->open((string)$user['id'], $candidate, $scan);
                $activeMessage = $candidate;
                $counts = $this->mailRepo->getFolderCounts((string)$user['id']);
                $messages = $this->mailRepo->getMessages((string)$user['id'], $folder, $search);
            }
        }

        if ($request->wantsJson()) {
            return Response::json([
                'needs_claim' => false,
                'mail_domain' => $mailDomain,
                'mailbox' => $mailbox,
                'folder' => $folder,
                'counts' => $counts,
                'messages' => $messages,
                'active_jail' => $activeJail,
            ], 200, 'Mailbox messages retrieved.');
        }

        $html = $this->view->render('mail.index', [
            'appBase' => $appBase,
            'nonce' => $nonce,
            'csrfToken' => $csrfToken,
            'currentUser' => $user,
            'user' => $user,
            'hasMailAccess' => true,
            'needsClaim' => false,
            'mailDomain' => $mailDomain,
            'suggestedLocal' => $mailbox['local_part'],
            'mailbox' => $mailbox,
            'folder' => $folder,
            'search' => $search,
            'messages' => $messages,
            'counts' => $counts,
            'activeJail' => $activeJail,
            'activeMessage' => $activeMessage,
            'error' => $this->session ? $this->session->getFlash('mail_error') : null,
            'success' => $this->session ? $this->session->getFlash('mail_success') : null,
        ]);

        return Response::html($html);
    }

    public function listMessages(Request $request): Response
    {
        $gate = $this->authorizeMail($request);
        if ($gate['response'] !== null) {
            return $gate['response'];
        }
        $user = $gate['user'];

        $mailbox = $this->mailRepo->getMailboxForUser((string)$user['id']);
        if ($mailbox === null) {
            return Response::json([
                'needs_claim' => true,
                'mail_domain' => MailRepository::getMailDomain(),
                'messages' => [],
            ], 200, 'Mailbox not yet claimed.');
        }

        $folder = strtolower($request->getString('folder', 'inbox'));
        if (!in_array($folder, MailRepository::VALID_FOLDERS, true)) {
            $folder = 'inbox';
        }
        $search = trim($request->getString('q', ''));
        $messages = $this->mailRepo->getMessages((string)$user['id'], $folder, $search);
        $counts = $this->mailRepo->getFolderCounts((string)$user['id']);

        return Response::json([
            'mailbox' => $mailbox,
            'folder' => $folder,
            'counts' => $counts,
            'messages' => $messages,
        ], 200, 'Messages retrieved.');
    }

    public function claim(Request $request): Response
    {
        $gate = $this->authorizeMail($request);
        if ($gate['response'] !== null) {
            return $gate['response'];
        }
        $user = $gate['user'];

        if ($csrfErr = $this->verifyCsrf($request)) {
            return $csrfErr;
        }

        $appBase = Request::getAppBase();
        $rawLocal = $request->getString('local_part', '');

        try {
            $mailbox = $this->mailRepo->claimMailbox((string)$user['id'], $rawLocal);
        } catch (InvalidArgumentException $e) {
            if ($request->wantsJson()) {
                return Response::error($e->getMessage(), 422, 'INVALID_MAILBOX_HANDLE');
            }
            if ($this->session !== null) {
                $this->session->flash('mail_error', $e->getMessage());
            }
            return Response::redirect($appBase . '/mail/');
        } catch (RuntimeException $e) {
            if ($request->wantsJson()) {
                return Response::error($e->getMessage(), 409, 'MAILBOX_TAKEN');
            }
            if ($this->session !== null) {
                $this->session->flash('mail_error', $e->getMessage());
            }
            return Response::redirect($appBase . '/mail/');
        }

        $this->userRepo->update((string)$user['id'], ['mailbox_address' => $mailbox['address']]);
        $this->auth->updateSessionUser(['mailbox_address' => $mailbox['address']]);
        $this->audit($request, (string)$user['id'], 'MAILBOX_CLAIM', 'mailboxes', $mailbox['id'], null, ['address' => $mailbox['address']]);

        if ($request->wantsJson()) {
            return Response::json(['mailbox' => $mailbox], 201, "Mailbox '{$mailbox['address']}' claimed.");
        }

        if ($this->session !== null) {
            $this->session->flash('mail_success', "Mailbox {$mailbox['address']} is ready.");
        }
        return Response::redirect($appBase . '/mail/');
    }

    public function send(Request $request): Response
    {
        $gate = $this->authorizeMail($request);
        if ($gate['response'] !== null) {
            return $gate['response'];
        }
        $user = $gate['user'];

        if ($csrfErr = $this->verifyCsrf($request)) {
            return $csrfErr;
        }

        $appBase = Request::getAppBase();
        $mailbox = $this->mailRepo->getMailboxForUser((string)$user['id']);
        if ($mailbox === null) {
            if ($request->wantsJson()) {
                return Response::error('Please claim your Nexus email address first.', 422, 'MAILBOX_REQUIRED');
            }
            return Response::redirect($appBase . '/mail/');
        }

        $mode = $request->getString('_action', 'send') === 'draft' ? 'draft' : 'send';
        $to = strtolower(trim($request->getString('to', '')));
        $subject = trim($request->getString('subject', ''));
        $bodyText = trim($request->getString('body', ''));
        $draftId = trim($request->getString('draft_id', ''));

        if ($mode === 'send') {
            if ($to === '' || !filter_var($to, FILTER_VALIDATE_EMAIL)) {
                if ($request->wantsJson()) {
                    return Response::error('Please specify a valid recipient email address.', 422, 'INVALID_RECIPIENT');
                }
                if ($this->session !== null) {
                    $this->session->flash('mail_error', 'Please enter a valid recipient email address.');
                }
                return Response::redirect($appBase . '/mail/');
            }

            if ($subject === '' || $bodyText === '') {
                if ($request->wantsJson()) {
                    return Response::error('Subject and message body are required.', 422, 'MISSING_FIELDS');
                }
                if ($this->session !== null) {
                    $this->session->flash('mail_error', 'Subject and message body cannot be empty.');
                }
                return Response::redirect($appBase . '/mail/');
            }
        }

        $scan = $this->scanner->scan($subject, $bodyText);
        if ($mode === 'send' && $scan['status'] === 'infected') {
            $reason = 'Virus Scanner blocked outbound email: ' . implode('; ', $scan['threats']);
            $this->audit($request, (string)$user['id'], 'MAIL_AV_BLOCKED', 'mail_messages', null, null, [
                'to' => $to,
                'subject' => $subject,
                'threats' => $scan['threats'],
            ]);

            if ($request->wantsJson()) {
                return Response::error($reason, 422, 'VIRUS_DETECTED');
            }
            if ($this->session !== null) {
                $this->session->flash('mail_error', $reason);
            }
            return Response::redirect($appBase . '/mail/');
        }

        if ($draftId !== '') {
            $this->mailRepo->deleteMessageForever((string)$user['id'], $draftId);
        }

        if ($mode === 'draft') {
            $draft = $this->mailRepo->saveMessage([
                'owner_user_id' => (string)$user['id'],
                'mailbox_address' => $mailbox['address'],
                'folder' => 'drafts',
                'from_name' => (string)($user['name'] ?? $mailbox['local_part']),
                'from_address' => $mailbox['address'],
                'to_address' => $to,
                'subject' => $subject !== '' ? $subject : '(Untitled Draft)',
                'body_text' => $bodyText,
                'is_read' => true,
            ]);

            if ($request->wantsJson()) {
                return Response::json(['message' => $draft], 200, 'Draft saved.');
            }
            if ($this->session !== null) {
                $this->session->flash('mail_success', 'Draft saved.');
            }
            return Response::redirect($appBase . '/mail/?folder=drafts');
        }

        // Save copy in sender's Sent folder
        $sentMessage = $this->mailRepo->saveMessage([
            'owner_user_id' => (string)$user['id'],
            'mailbox_address' => $mailbox['address'],
            'folder' => 'sent',
            'from_name' => (string)($user['name'] ?? $mailbox['local_part']),
            'from_address' => $mailbox['address'],
            'to_address' => $to,
            'subject' => $subject,
            'body_text' => $bodyText,
            'is_read' => true,
        ]);

        // Deliver directly if recipient holds a Nexus mailbox
        $recipientMailbox = $this->mailRepo->getMailboxByAddress($to);
        if ($recipientMailbox !== null) {
            $this->mailRepo->saveMessage([
                'owner_user_id' => $recipientMailbox['user_id'],
                'mailbox_address' => $recipientMailbox['address'],
                'folder' => 'inbox',
                'from_name' => (string)($user['name'] ?? $mailbox['local_part']),
                'from_address' => $mailbox['address'],
                'to_address' => $recipientMailbox['address'],
                'subject' => $subject,
                'body_text' => $bodyText,
                'is_read' => false,
            ]);
        }

        // Dispatch via SMTP Mailer for external recipients when configured
        $smtpDispatched = false;
        if ($recipientMailbox === null) {
            try {
                $mailer = $this->mailer ?? new Mailer(
                    (string)Env::get('MAIL_HOST', '127.0.0.1'),
                    (int)Env::get('MAIL_PORT', 1025),
                    Env::get('MAIL_USERNAME'),
                    Env::get('MAIL_PASSWORD'),
                    Env::get('MAIL_ENCRYPTION'),
                    $mailbox['address'],
                    (string)($user['name'] ?? 'Dreambox Nexus')
                );
                $smtpDispatched = $mailer->send($to, $subject, $scan['sanitized_html'], $bodyText);
            } catch (\Throwable) {
                $smtpDispatched = false;
            }
        }

        $this->audit($request, (string)$user['id'], 'MAIL_SENT', 'mail_messages', $sentMessage['id'], null, [
            'from' => $mailbox['address'],
            'to' => $to,
            'internal_delivery' => $recipientMailbox !== null,
            'smtp_dispatched' => $smtpDispatched,
            'scan_status' => $sentMessage['scan_status'],
        ]);

        if ($request->wantsJson()) {
            return Response::json([
                'message' => $sentMessage,
                'internal_delivery' => $recipientMailbox !== null,
                'smtp_dispatched' => $smtpDispatched,
            ], 200, 'Message scanned and sent.');
        }

        if ($this->session !== null) {
            $note = $recipientMailbox !== null
                ? "Email scanned cleanly and delivered internally to {$to}."
                : ($smtpDispatched
                    ? "Email scanned cleanly and dispatched via SMTP to {$to}."
                    : "Email scanned cleanly and recorded in Sent (local SMTP relay offline).");
            $this->session->flash('mail_success', $note);
        }

        return Response::redirect($appBase . '/mail/?folder=sent');
    }

    public function openJail(Request $request, string $id): Response
    {
        $gate = $this->authorizeMail($request);
        if ($gate['response'] !== null) {
            return $gate['response'];
        }
        $user = $gate['user'];

        if ($csrfErr = $this->verifyCsrf($request)) {
            return $csrfErr;
        }

        $message = $this->mailRepo->getMessage((string)$user['id'], $id);
        if ($message === null) {
            return Response::error('Email message not found.', 404, 'NOT_FOUND');
        }

        if (empty($message['is_read'])) {
            $message = $this->mailRepo->updateMessage((string)$user['id'], $id, ['is_read' => true]) ?? $message;
        }

        // Destroy any previous active reader jail owned by this user before launching a fresh isolated jail
        $this->jail->destroyAllForUser((string)$user['id']);

        $scan = $this->scanner->scan(
            (string)($message['subject'] ?? ''),
            (string)($message['body_text'] ?? ''),
            (string)($message['body_html'] ?? ''),
            (array)($message['attachments'] ?? [])
        );

        $jailData = $this->jail->open((string)$user['id'], $message, $scan);
        $this->audit($request, (string)$user['id'], 'MAIL_JAIL_OPEN', 'mail_messages', $id, null, [
            'jail_id' => $jailData['jail_id'],
            'scan_status' => $scan['status'],
        ]);

        return Response::json([
            'message' => $message,
            'jail' => $jailData,
            'scan' => $scan,
            'counts' => $this->mailRepo->getFolderCounts((string)$user['id']),
        ], 200, 'Controlled Reader Jail active.');
    }

    public function serveJailFrame(Request $request, string $jailId): Response
    {
        $gate = $this->authorizeMail($request);
        if ($gate['response'] !== null) {
            return $gate['response'];
        }
        $user = $gate['user'];

        $jailData = $this->jail->get((string)$user['id'], $jailId);
        if ($jailData === null) {
            return Response::html('<p style="font-family:sans-serif;color:#64748b;padding:20px;">Reader Jail expired or destroyed.</p>', 404);
        }

        return Response::html((string)$jailData['sandbox_html'], 200, [
            'Cache-Control' => 'no-store, no-cache, must-revalidate',
        ]);
    }

    public function closeJail(Request $request, string $jailId): Response
    {
        $gate = $this->authorizeMail($request);
        if ($gate['response'] !== null) {
            return $gate['response'];
        }
        $user = $gate['user'];

        if ($csrfErr = $this->verifyCsrf($request)) {
            return $csrfErr;
        }

        $destroyed = $this->jail->destroy((string)$user['id'], $jailId);
        $this->audit($request, (string)$user['id'], 'MAIL_JAIL_CLOSE', 'mail_jails', $jailId, null, [
            'destroyed' => $destroyed,
        ]);

        if ($request->wantsJson()) {
            return Response::json([
                'jail_id' => $jailId,
                'destroyed' => $destroyed,
            ], 200, $destroyed ? 'Controlled Reader Jail torn down and shredded.' : 'Jail already closed.');
        }

        return Response::redirect(Request::getAppBase() . '/mail/');
    }

    public function mutateMessage(Request $request, string $id): Response
    {
        $gate = $this->authorizeMail($request);
        if ($gate['response'] !== null) {
            return $gate['response'];
        }
        $user = $gate['user'];

        if ($csrfErr = $this->verifyCsrf($request)) {
            return $csrfErr;
        }

        $message = $this->mailRepo->getMessage((string)$user['id'], $id);
        if ($message === null) {
            if ($request->wantsJson()) {
                return Response::error('Email message not found.', 404, 'NOT_FOUND');
            }
            return Response::redirect(Request::getAppBase() . '/mail/');
        }

        $action = $request->getString('_action', '');
        $redirectFolder = $request->getString('return_folder', (string)$message['folder']);
        $updated = $message;

        switch ($action) {
            case 'star':
                $updated = $this->mailRepo->updateMessage((string)$user['id'], $id, ['is_starred' => true]);
                break;
            case 'unstar':
                $updated = $this->mailRepo->updateMessage((string)$user['id'], $id, ['is_starred' => false]);
                break;
            case 'toggle_star':
                $updated = $this->mailRepo->updateMessage((string)$user['id'], $id, ['is_starred' => empty($message['is_starred'])]);
                break;
            case 'mark_unread':
                $updated = $this->mailRepo->updateMessage((string)$user['id'], $id, ['is_read' => false]);
                break;
            case 'mark_read':
                $updated = $this->mailRepo->updateMessage((string)$user['id'], $id, ['is_read' => true]);
                break;
            case 'trash':
                $updated = $this->mailRepo->updateMessage((string)$user['id'], $id, ['folder' => 'trash']);
                break;
            case 'restore':
                $updated = $this->mailRepo->updateMessage((string)$user['id'], $id, ['folder' => 'inbox']);
                break;
            case 'delete_forever':
                $this->mailRepo->deleteMessageForever((string)$user['id'], $id);
                $updated = null;
                break;
            default:
                if ($request->wantsJson()) {
                    return Response::error('Unsupported message action.', 422, 'INVALID_ACTION');
                }
        }

        $counts = $this->mailRepo->getFolderCounts((string)$user['id']);

        if ($request->wantsJson()) {
            return Response::json([
                'message' => $updated,
                'deleted' => $action === 'delete_forever',
                'counts' => $counts,
            ], 200, 'Message updated.');
        }

        return Response::redirect(Request::getAppBase() . '/mail/?folder=' . urlencode($redirectFolder));
    }

    private function authorizeMail(Request $request): array
    {
        $user = $this->auth->user($request);
        $appBase = Request::getAppBase();

        if ($user === null) {
            return [
                'user' => null,
                'response' => $request->wantsJson()
                    ? Response::error('Authentication required.', 401, 'UNAUTHORIZED')
                    : Response::redirect($appBase . '/auth/login/'),
            ];
        }

        $fresh = $this->userRepo->findById((string)$user['id']);
        if ($fresh !== null) {
            $user = array_merge($user, [
                'roles' => $fresh['roles'] ?? $user['roles'] ?? ['Member'],
                'groups' => $fresh['groups'] ?? $user['groups'] ?? [],
                'mail_access' => !empty($fresh['mail_access']),
                'mailbox_address' => $fresh['mailbox_address'] ?? null,
                'must_change_password' => !empty($fresh['must_change_password']),
            ]);
        }

        if (!empty($user['must_change_password'])) {
            return [
                'user' => $user,
                'response' => $request->wantsJson()
                    ? Response::error('Password rotation required before accessing Nexus Webmail.', 403, 'PASSWORD_CHANGE_REQUIRED')
                    : Response::redirect($appBase . '/auth/password/change/'),
            ];
        }

        $hasMailAccess = $this->roleMgr->userHasPermission($user, 'mail.access') || !empty($user['mail_access']);
        if (!$hasMailAccess) {
            if ($request->wantsJson()) {
                return [
                    'user' => $user,
                    'response' => Response::error('Forbidden: mail.access permission required.', 403, 'MAIL_PERMISSION_DENIED'),
                ];
            }

            if ($this->session !== null) {
                $this->session->start();
            }

            $html = $this->view->render('mail.index', [
                'appBase' => $appBase,
                'nonce' => Headers::getNonce(),
                'csrfToken' => $this->csrf->generate($this->session?->getId()),
                'currentUser' => $user,
                'user' => $user,
                'hasMailAccess' => false,
                'needsClaim' => false,
                'mailDomain' => MailRepository::getMailDomain(),
                'suggestedLocal' => '',
                'mailbox' => null,
                'folder' => 'inbox',
                'search' => '',
                'messages' => [],
                'counts' => ['inbox_unread' => 0, 'inbox' => 0, 'starred' => 0, 'sent' => 0, 'drafts' => 0, 'trash' => 0],
                'activeJail' => null,
                'activeMessage' => null,
                'error' => null,
                'success' => null,
            ]);

            return [
                'user' => $user,
                'response' => Response::html($html, 403),
            ];
        }

        return ['user' => $user, 'response' => null];
    }

    private function verifyCsrf(Request $request): ?Response
    {
        if ($this->session !== null) {
            $this->session->start();
        }
        $sessionId = $this->session ? $this->session->getId() : '';
        if (!$this->csrf->verifyRequest($request, $sessionId)) {
            if ($request->wantsJson()) {
                return Response::error('CSRF token validation failed.', 419, 'CSRF_INVALID');
            }
            if ($this->session !== null) {
                $this->session->flash('mail_error', 'Invalid CSRF security token. Please reload and try again.');
            }
            return Response::redirect(Request::getAppBase() . '/mail/');
        }
        return null;
    }

    private function audit(
        Request $request,
        string $userId,
        string $action,
        string $resource,
        ?string $resourceId,
        ?array $before,
        ?array $after
    ): void {
        try {
            (new AuditLogger($this->db))->log(
                $request->getTraceId(),
                $userId,
                $action,
                $resource,
                $resourceId,
                $request->getClientIp(),
                $request->getUserAgent(),
                $before,
                $after
            );
        } catch (\Throwable) {}
    }
}
