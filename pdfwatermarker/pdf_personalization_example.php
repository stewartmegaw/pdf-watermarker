<?php

class AlphaPDF extends FPDI_with_annots {

    var $extgstates = array();

    // alpha: real value from 0 (transparent) to 1 (opaque)
    // bm:    blend mode, one of the following:
    //          Normal, Multiply, Screen, Overlay, Darken, Lighten, ColorDodge, ColorBurn,
    //          HardLight, SoftLight, Difference, Exclusion, Hue, Saturation, Color, Luminosity
    function SetAlpha($alpha, $bm = 'Normal') {
        // set alpha for stroking (CA) and non-stroking (ca) operations
        $gs = $this->AddExtGState(array('ca' => $alpha, 'CA' => $alpha, 'BM' => '/' . $bm));
        $this->SetExtGState($gs);
    }

    function AddExtGState($parms) {
        $n = count($this->extgstates) + 1;
        $this->extgstates[$n]['parms'] = $parms;
        return $n;
    }

    function SetExtGState($gs) {
        $this->_out(sprintf('/GS%d gs', $gs));
    }

    function _enddoc() {
        if (!empty($this->extgstates) && $this->PDFVersion < '1.4')
            $this->PDFVersion = '1.4';
        parent::_enddoc();
    }

    function _putextgstates() {
        for ($i = 1; $i <= count($this->extgstates); $i++) {
            $this->_newobj();
            $this->extgstates[$i]['n'] = $this->n;
            $this->_out('<</Type /ExtGState');
            $parms = $this->extgstates[$i]['parms'];
            $this->_out(sprintf('/ca %.3F', $parms['ca']));
            $this->_out(sprintf('/CA %.3F', $parms['CA']));
            $this->_out('/BM ' . $parms['BM']);
            $this->_out('>>');
            $this->_out('endobj');
        }
    }

    function _putresourcedict() {
        parent::_putresourcedict();
        $this->_out('/ExtGState <<');
        foreach ($this->extgstates as $k => $extgstate)
            $this->_out('/GS' . $k . ' ' . $extgstate['n'] . ' 0 R');
        $this->_out('>>');
    }

    function _putresources() {
        $this->_putextgstates();
        parent::_putresources();
    }

}

// uses FPDI to append another PDF file, watermarking each page with a message
class FPDI_AppendWithWatermark extends AlphaPDF {

    var $angle = 0;

    function AppendPDFWithWatermarkMessage($file, $message, $largeMessage = '') {
        $pagecount = $this->setSourceFile($file);
        for ($i = 1; $i <= $pagecount; $i++) {
            $tplidx = $this->ImportPage($i);
            $s = $this->getTemplatesize($tplidx);
            $this->AddPage('P', array($s['w'], $s['h']));
            $this->useTemplate($tplidx);
            // watermark (a message printed vertically along the left margin)
            $this->SetAutoPageBreak(FALSE);
            $this->SetXY(50, -5);
            $this->Rotate(0);
            $this->SetTextColor(102, 102, 102);
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 5, utf8_decode($message), '', 1, 'L');
            $this->Rotate(0); // outputs Q to balance "q" added by the previous call to Rotate
            // Left
            $this->SetXY(2, 200);
            $this->Rotate(90);
            $this->SetTextColor(102, 102, 102);
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 5, utf8_decode($message), '', 1, 'L');
            $this->Rotate(0); // outputs Q to balance "q" added by the previous call to Rotate
            // Right
            $this->SetXY(-6, 200);
            $this->Rotate(90);
            $this->SetTextColor(102, 102, 102);
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 5, utf8_decode($message), '', 1, 'L');
            $this->Rotate(0); // outputs Q to balance "q" added by the previous call to Rotate


            if (!empty($largeMessage)) {
                $this->Image('./data/assets/watermark.png', 0, 0, $this->w, $this->h);
            }
        }
    }

    function Rotate($angle, $x = -1, $y = -1) {
        if ($x == -1)
            $x = $this->x;
        if ($y == -1)
            $y = $this->y;
        if ($this->angle != 0)
            $this->_out('Q');
        $this->angle = $angle;
        if ($angle != 0) {
            $angle *= M_PI / 180;
            $c = cos($angle);
            $s = sin($angle);
            $cx = $x * $this->k;
            $cy = ($this->h - $y) * $this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
    }

    function _endpage() {
        if ($this->angle != 0) {
            $this->angle = 0;
            $this->_out('Q');
        }
        parent::_endpage();
    }

}
