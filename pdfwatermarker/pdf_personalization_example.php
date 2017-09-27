<?php
// uses FPDI to append another PDF file, watermarking each page with a message
class FPDI_AppendWithWatermark extends FPDI_with_annots {
     var $angle=0;
    function AppendPDFWithWatermarkMessage($file, $message) {
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
            $this->Cell(0, 5, utf8_decode($message),'',1,'L');
            $this->Rotate(0); // outputs Q to balance "q" added by the previous call to Rotate
            
            // Left
            $this->SetXY(2,200);
            $this->Rotate(90);
            $this->SetTextColor(102, 102, 102);
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 5, utf8_decode($message),'',1,'L');
            $this->Rotate(0); // outputs Q to balance "q" added by the previous call to Rotate
            
            // Right
            $this->SetXY(-6,200);
            $this->Rotate(90);
            $this->SetTextColor(102, 102, 102);
            $this->SetFont('Arial', '', 8);
            $this->Cell(0, 5, utf8_decode($message),'',1,'L');
            $this->Rotate(0); // outputs Q to balance "q" added by the previous call to Rotate
        }
    }
   

    function Rotate($angle, $x=-1, $y=-1)
    {
        if($x==-1)
            $x=$this->x;
        if($y==-1)
            $y=$this->y;
        if($this->angle!=0)
            $this->_out('Q');
        $this->angle=$angle;
        if($angle!=0)
        {
            $angle*=M_PI/180;
            $c=cos($angle);
            $s=sin($angle);
            $cx=$x*$this->k;
            $cy=($this->h-$y)*$this->k;
            $this->_out(sprintf('q %.5F %.5F %.5F %.5F %.2F %.2F cm 1 0 0 1 %.2F %.2F cm', $c, $s, -$s, $c, $cx, $cy, -$cx, -$cy));
        }
}

function _endpage()
{
    if($this->angle!=0)
    {
        $this->angle=0;
        $this->_out('Q');
    }
    parent::_endpage();
}
}
