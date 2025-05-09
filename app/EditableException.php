<?php

namespace MyApp;

use Exception;

class EditableException extends Exception
{
    public function setMessage(string $message): self
    {
        $this->message = $message;
        return $this;
    }

    public function setFile(string $file): self
    {
        $this->file = $file;
        return $this;
    }

    public function setLine(int $line): self
    {
        $this->line = $line;
        return $this;
    }
}
