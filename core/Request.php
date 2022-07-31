<?php

namespace Nest\Routing;

class Request
{
    private array $parameters;

    public string|null $path;
    public string $method;

    public function __construct()
    {
        $path = preg_replace('/(.*?)\?.*/m', '$1', $_SERVER['REQUEST_URI']);

        $this->path = urldecode($path);
        $this->method = $_SERVER['REQUEST_METHOD'];

        if ($this->method == 'POST') {
            $valid = true;
            $this->parameters = $_POST;

            if (!isset($this->parameters['csrf_token'])) $valid = false;
            else if (!isset($_SESSION['csrf_token'])) $valid = false;
            else if ($this->parameters['csrf_token'] != $_SESSION['csrf_token']) $valid = false;

            if (!$valid) {
                die(
                    file_get_contents(__DIR__.'/templates/page_expired.php')
                );
            }
        } else {
            $this->parameters = $_GET;
        }

        foreach ($this->parameters as $key => $value) {
            $this->$key = $value;
        }

        $this->update();
    }

    public function update()
    {
        foreach ($this->parameters as $key => $value) {
            $this->$key = $value;
        }
    }

    public function manipulate($key, $value): void
    {
        $this->parameters[$key] = $value;
    }

    public function all(): array
    {
        return $this->parameters;
    }
}
