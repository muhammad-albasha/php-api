<?php

class ERechnungParser
{
    public static function parseERechnung($filePath)
    {
        $fileExtension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));
        $fileName = basename($filePath);
        
        $metadata = [
            'type' => 'unknown',
            'rechnungsnummer' => null,
            'datum' => null,
            'betrag' => null,
            'waehrung' => 'EUR',
            'lieferant' => null,
            'kunde' => null,
            'umsatzsteuer' => null,
            'faelligkeitsdatum' => null
        ];
        
        switch ($fileExtension) {
            case 'xml':
                $metadata = self::parseXML($filePath);
                break;
            case 'pdf':
                $metadata = self::parseZUGFeRDPDF($filePath);
                break;
            case 'json':
                $metadata = self::parseJSON($filePath);
                break;
            default:
                $metadata = self::parseFromFilename($fileName);
                break;
        }
        
        return $metadata;
    }
    
    private static function parseXML($filePath)
    {
        $metadata = [
            'type' => 'XRechnung',
            'rechnungsnummer' => null,
            'datum' => null,
            'betrag' => null,
            'waehrung' => 'EUR',
            'lieferant' => null,
            'kunde' => null,
            'umsatzsteuer' => null,
            'faelligkeitsdatum' => null
        ];
        
        try {
            $xmlContent = file_get_contents($filePath);
            $xml = simplexml_load_string($xmlContent);
            
            if ($xml === false) {
                return $metadata;
            }
            
            // XRechnung/UBL Format
            if (isset($xml->Invoice)) {
                $invoice = $xml->Invoice;
                
                // Rechnungsnummer
                if (isset($invoice->ID)) {
                    $metadata['rechnungsnummer'] = (string)$invoice->ID;
                }
                
                // Datum
                if (isset($invoice->IssueDate)) {
                    $metadata['datum'] = (string)$invoice->IssueDate;
                }
                
                // Fälligkeitsdatum
                if (isset($invoice->DueDate)) {
                    $metadata['faelligkeitsdatum'] = (string)$invoice->DueDate;
                }
                
                // Gesamtbetrag
                if (isset($invoice->LegalMonetaryTotal->TaxInclusiveAmount)) {
                    $metadata['betrag'] = (string)$invoice->LegalMonetaryTotal->TaxInclusiveAmount;
                    $metadata['waehrung'] = (string)$invoice->LegalMonetaryTotal->TaxInclusiveAmount['currencyID'] ?? 'EUR';
                }
                
                // Lieferant
                if (isset($invoice->AccountingSupplierParty->Party->PartyName->Name)) {
                    $metadata['lieferant'] = (string)$invoice->AccountingSupplierParty->Party->PartyName->Name;
                }
                
                // Kunde
                if (isset($invoice->AccountingCustomerParty->Party->PartyName->Name)) {
                    $metadata['kunde'] = (string)$invoice->AccountingCustomerParty->Party->PartyName->Name;
                }
                
                // Umsatzsteuer
                if (isset($invoice->TaxTotal->TaxAmount)) {
                    $metadata['umsatzsteuer'] = (string)$invoice->TaxTotal->TaxAmount;
                }
            }
            
            // Cross Industry Invoice (CII) Format
            elseif (isset($xml->ExchangedDocument)) {
                $doc = $xml->ExchangedDocument;
                
                if (isset($doc->ID)) {
                    $metadata['rechnungsnummer'] = (string)$doc->ID;
                }
                
                if (isset($doc->IssueDateTime->DateTimeString)) {
                    $metadata['datum'] = (string)$doc->IssueDateTime->DateTimeString;
                }
                
                // Weitere CII-spezifische Felder...
            }
            
        } catch (Exception $e) {
            error_log("XML Parse Error: " . $e->getMessage());
        }
        
        return $metadata;
    }
    
    private static function parseZUGFeRDPDF($filePath)
    {
        $metadata = [
            'type' => 'ZUGFeRD',
            'rechnungsnummer' => null,
            'datum' => null,
            'betrag' => null,
            'waehrung' => 'EUR',
            'lieferant' => null,
            'kunde' => null,
            'umsatzsteuer' => null,
            'faelligkeitsdatum' => null
        ];
        
        // Versuche XML aus PDF zu extrahieren (ZUGFeRD)
        try {
            // Einfache Methode: Nach eingebetteten XML-Daten suchen
            $pdfContent = file_get_contents($filePath);
            
            // Suche nach XML-Start in PDF
            $xmlStart = strpos($pdfContent, '<?xml');
            if ($xmlStart !== false) {
                $xmlEnd = strpos($pdfContent, '</CrossIndustryInvoice>', $xmlStart);
                if ($xmlEnd === false) {
                    $xmlEnd = strpos($pdfContent, '</Invoice>', $xmlStart);
                }
                
                if ($xmlEnd !== false) {
                    $xmlContent = substr($pdfContent, $xmlStart, $xmlEnd - $xmlStart + 25);
                    $xml = simplexml_load_string($xmlContent);
                    
                    if ($xml !== false) {
                        // XML-Parsing wie oben
                        return self::parseXMLObject($xml, $metadata);
                    }
                }
            }
            
            // Fallback: Filename-basierte Extraktion
            return self::parseFromFilename(basename($filePath));
            
        } catch (Exception $e) {
            error_log("ZUGFeRD Parse Error: " . $e->getMessage());
            return self::parseFromFilename(basename($filePath));
        }
    }
    
    private static function parseJSON($filePath)
    {
        $metadata = [
            'type' => 'JSON-E-Rechnung',
            'rechnungsnummer' => null,
            'datum' => null,
            'betrag' => null,
            'waehrung' => 'EUR',
            'lieferant' => null,
            'kunde' => null,
            'umsatzsteuer' => null,
            'faelligkeitsdatum' => null
        ];
        
        try {
            $jsonContent = file_get_contents($filePath);
            $data = json_decode($jsonContent, true);
            
            if ($data === null) {
                return $metadata;
            }
            
            // Gängige JSON-E-Rechnung-Strukturen
            $metadata['rechnungsnummer'] = $data['invoiceNumber'] ?? $data['invoice_number'] ?? $data['id'] ?? null;
            $metadata['datum'] = $data['invoiceDate'] ?? $data['invoice_date'] ?? $data['date'] ?? null;
            $metadata['betrag'] = $data['totalAmount'] ?? $data['total_amount'] ?? $data['total'] ?? null;
            $metadata['waehrung'] = $data['currency'] ?? $data['currencyCode'] ?? 'EUR';
            $metadata['lieferant'] = $data['supplier']['name'] ?? $data['vendor']['name'] ?? null;
            $metadata['kunde'] = $data['customer']['name'] ?? $data['buyer']['name'] ?? null;
            $metadata['umsatzsteuer'] = $data['taxAmount'] ?? $data['tax_amount'] ?? null;
            $metadata['faelligkeitsdatum'] = $data['dueDate'] ?? $data['due_date'] ?? null;
            
        } catch (Exception $e) {
            error_log("JSON Parse Error: " . $e->getMessage());
        }
        
        return $metadata;
    }
    
    private static function parseFromFilename($fileName)
    {
        $metadata = [
            'type' => 'Dateiname-basiert',
            'rechnungsnummer' => null,
            'datum' => null,
            'betrag' => null,
            'waehrung' => 'EUR',
            'lieferant' => null,
            'kunde' => null,
            'umsatzsteuer' => null,
            'faelligkeitsdatum' => null
        ];
        
        // Verschiedene Dateinamen-Patterns
        $patterns = [
            // Pattern: RE-2025001-100.50-FIRMA.pdf
            '/RE-(\d+)-(\d+\.?\d*)-([A-Za-z]+)\./',
            // Pattern: invoice_2025001_FIRMA_100.50.pdf
            '/invoice_(\d+)_([A-Za-z]+)_(\d+\.?\d*)\./',
            // Pattern: rechnung-FIRMA-2025-08-14-100.50.pdf
            '/rechnung-([A-Za-z]+)-(\d{4}-\d{2}-\d{2})-(\d+\.?\d*)\./',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match($pattern, $fileName, $matches)) {
                switch (count($matches)) {
                    case 4: // 3 Gruppen + vollständiger Match
                        $metadata['rechnungsnummer'] = $matches[1];
                        $metadata['betrag'] = $matches[2];
                        $metadata['lieferant'] = $matches[3];
                        break;
                }
                break;
            }
        }
        
        // Datum aus Dateiname extrahieren
        if (preg_match('/(\d{4}-\d{2}-\d{2})/', $fileName, $dateMatches)) {
            $metadata['datum'] = $dateMatches[1];
        }
        
        return $metadata;
    }
    
    private static function parseXMLObject($xml, $metadata)
    {
        // Hilfsfunktion für XML-Parsing
        if (isset($xml->ExchangedDocument->ID)) {
            $metadata['rechnungsnummer'] = (string)$xml->ExchangedDocument->ID;
        }
        
        return $metadata;
    }
    
    public static function getMimeType($extension)
    {
        $eRechnungMimeTypes = [
            'xml' => 'application/xml',
            'pdf' => 'application/pdf',
            'json' => 'application/json',
            'txt' => 'text/plain',
            'csv' => 'text/csv'
        ];
        
        return $eRechnungMimeTypes[strtolower($extension)] ?? 'application/octet-stream';
    }
}
