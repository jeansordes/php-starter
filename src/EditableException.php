<?php

namespace MyApp;

class EditableException extends \Exception
{
    public function setMessage($m) {
        $this->message = $m;
    }

    public function setFile($f) {
        $this->file = $f;
    }

    public function setLine($l) {
        $this->line = $l;
    }
}
