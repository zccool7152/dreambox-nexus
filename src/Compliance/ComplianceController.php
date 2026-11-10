<?php
declare(strict_types=1);

namespace Nexus\Compliance;

use Nexus\Core\Request;
use Nexus\Core\Response;
use Nexus\Database\Database;
use Nexus\View\View;

/**
 * Regulatory Compliance & Standards Matrix Controller
 *
 * Serves real-time regulatory compliance matrices, live verification results,
 * and mandate breakdown across PCI-DSS, HIPAA, NIST CSF, GDPR, and OWASP.
 */
class ComplianceController
{
    private Database $db;
    private string $baseDir;

    public function __construct(Database $db, ?string $baseDir = null)
    {
        $this->db = $db;
        $this->baseDir = $baseDir ?? dirname(__DIR__, 2);
    }

    public function index(Request $request): Response
    {
        $verifier = new ComplianceVerifier($this->db, $this->baseDir . '/storage');
        $report = $verifier->getFullReport();

        $accept = $request->getHeader('accept', '');
        $format = $request->getString('format');
        $wantsJson = str_contains($accept, 'application/json') || $format === 'json';

        // When accessed from a browser without explicit json format request, render HTML HUD
        if (!$wantsJson && str_contains($accept, 'text/html')) {
            $sess = new \Nexus\Security\Session($this->db);
            $auth = new \Nexus\Auth\Auth($sess, new \Nexus\Crypto\Hasher(), $this->db);
            $user = $auth->user($request);

            $view = new View($this->baseDir . '/resources/views');
            $html = $view->render('compliance', [
                'report' => $report,
                'traceId' => $request->getTraceId(),
                'user' => $user,
                'currentUser' => $user,
                'appBase' => Request::getAppBase(),
            ]);
            return Response::html($html);
        }

        return Response::json($report, 'Regulatory compliance matrix retrieved.', 200, [], $request->getTraceId());
    }
}

