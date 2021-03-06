<?php
declare(strict_types=1);

namespace Lookyman\NetteOAuth2Server\UI;

use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Exception\OAuthServerException;
use Lookyman\NetteOAuth2Server\Psr7\ApplicationPsr7ResponseInterface;
use Lookyman\NetteOAuth2Server\RedirectConfig;
use Lookyman\NetteOAuth2Server\Storage\IAuthorizationRequestSerializer;
use Nette\Application\AbortException;
use Nette\Application\UI\Presenter;
use Nette\Http\IRequest;
use Nette\Http\IResponse;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;

class OAuth2Presenter extends Presenter implements LoggerAwareInterface
{

	use LoggerAwareTrait;
	use Psr7Trait;

	public const SESSION_NAMESPACE = 'nette-oauth2-server';

	/**
	 * @var IAuthorizationRequestSerializer
	 * @inject
	 */
	public $authorizationRequestSerializer;

	/**
	 * @var AuthorizationServer
	 * @inject
	 */
	public $authorizationServer;

	/**
	 * @var RedirectConfig
	 * @inject
	 */
	public $redirectConfig;

	public function actionAccessToken(): void
	{
		if (!$this->getHttpRequest()->isMethod(IRequest::POST)) {
			$body = $this->createStream();
			$body->write('Method not allowed');
			$this->sendResponse($this->createResponse()->withStatus(IResponse::S405_METHOD_NOT_ALLOWED)->withBody($body));
		}

		$response = $this->createResponse();
		try {
			/** @var ApplicationPsr7ResponseInterface $response */
			$response = $this->authorizationServer->respondToAccessTokenRequest($this->createServerRequest(), $response);
			$this->sendResponse($response);

		} catch (AbortException $e) {
			throw $e;

		} catch (OAuthServerException $e) {
			/** @var ApplicationPsr7ResponseInterface $response */
			$response = $e->generateHttpResponse($response);
			$this->sendResponse($response);

		} catch (\Throwable $e) {
			if ($this->logger) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
			}
			$body = $this->createStream();
			$body->write('Unknown error');
			$this->sendResponse($response->withStatus(IResponse::S500_INTERNAL_SERVER_ERROR)->withBody($body));
		}
	}

	public function actionAuthorize(): void
	{
		if (!$this->getHttpRequest()->isMethod(IRequest::GET)) {
			$body = $this->createStream();
			$body->write('Method not allowed');
			$this->sendResponse($this->createResponse()->withStatus(IResponse::S405_METHOD_NOT_ALLOWED)->withBody($body));
		}
		$response = $this->createResponse();
		try {
			$this->getSession(self::SESSION_NAMESPACE)->authorizationRequest = $this->authorizationRequestSerializer->serialize(
				$this->authorizationServer->validateAuthorizationRequest($this->createServerRequest())
			);
			if (!$this->getUser()->isLoggedIn()) {
				$this->redirect(...$this->redirectConfig->getLoginDestination());
			}
			$this->redirect(...$this->redirectConfig->getApproveDestination());

		} catch (AbortException $e) {
			throw $e;

		} catch (OAuthServerException $e) {
			/** @var ApplicationPsr7ResponseInterface $response */
			$response = $e->generateHttpResponse($response);
			$this->sendResponse($response);

		} catch (\Throwable $e) {
			if ($this->logger) {
				$this->logger->error($e->getMessage(), ['exception' => $e]);
			}
			$body = $this->createStream();
			$body->write('Unknown error');
			$this->sendResponse($response->withStatus(IResponse::S500_INTERNAL_SERVER_ERROR)->withBody($body));
		}
	}

}
