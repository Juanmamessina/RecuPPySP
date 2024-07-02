<?php

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Psr7\Response as SlimResponse;

class VerificarCamposMW
{
    private $camposRequeridos;

    public function __construct(array $camposRequeridos)
    {
        $this->camposRequeridos = $camposRequeridos;
    }

    public function __invoke(Request $request, RequestHandler $handler): Response
    {
        $parametros = $request->getQueryParams();

        foreach ($this->camposRequeridos as $campo) {
            if (!isset($parametros[$campo]) || empty($parametros[$campo])) {
                $response = new SlimResponse();
                $payload = json_encode(['error' => "Por favor complete el campo '$campo'"]);
                $response->getBody()->write($payload);
                return $response->withHeader('Content-Type', 'application/json')->withStatus(400);
            }
        }

        return $handler->handle($request);
    }
}
