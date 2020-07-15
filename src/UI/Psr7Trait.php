<?php
declare(strict_types=1);

namespace Lookyman\NetteOAuth2Server\UI;

use Laminas\Diactoros\ServerRequestFactory;
use Laminas\Diactoros\Stream;
use Lookyman\NetteOAuth2Server\Psr7\ApplicationPsr7ResponseInterface;
use Lookyman\NetteOAuth2Server\Psr7\Response;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Message\StreamInterface;

trait Psr7Trait
{

	protected function createServerRequest(): ServerRequestInterface
	{
		return ServerRequestFactory::fromGlobals();
	}

	protected function createResponse(): ApplicationPsr7ResponseInterface
	{
		return new Response();
	}

	protected function createStream(): StreamInterface
	{
		return new Stream('php://temp', 'r+');
	}

}
