<?php

namespace Drupal\gepsis\Controller;

use Twig\Extension\AbstractExtension;
use Drupal\user\Entity\User;
use Twig\TwigFunction;

class GetLinkFacturesAll extends AbstractExtension {

    // https://symfony.com/doc/current/templating/twig_extension.html

    public function getFunctions() {
        return [
            new TwigFunction('getLinkFacturesAll', [
                $this,
                'getLinkFacturesAll',
            ]),
        ];
    }

    // {{ getLinkFacturesAll({ INVOICE_AS_REPORT_PDF_TXT },{ INVOICE_NUMBER }) }}
    public function getLinkFacturesAll($invoiceAsReportPdfTxt, $invoiceNumber) {
        $user = User::load(\Drupal::currentUser()->id());
        $entrCode = $user->get('field_active_adherent_code')->value;
        $docPdf = $invoiceAsReportPdfTxt['INVOICE_AS_REPORT_PDF_TXT'];
        $invNumber = $invoiceNumber['INVOICE_NUMBER'];

        $decoded = base64_decode($docPdf);
        $pathFile = 'public://factures/facture_' . $invNumber . '_adherent_' . $entrCode . '.pdf';
        file_put_contents($pathFile, $decoded);
        $file = \Drupal::service('file_url_generator')
            ->generateAbsoluteString($pathFile);

        if (file_exists($pathFile)) {
            header('Content-Description: File Transfer');
            header('Content-Type: application/pdf');
            header('Expires: 0');
            header('Cache-Control: must-revalidate');
            header('Pragma: public');
            return $file;
        }
    }

}