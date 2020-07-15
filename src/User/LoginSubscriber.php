<?php
declare(strict_types=1);

namespace Lookyman\NetteOAuth2Server\User;

use Lookyman\NetteOAuth2Server\RedirectConfig;
use Lookyman\NetteOAuth2Server\UI\OAuth2Presenter;
use Nette\Application\Application;
use Nette\Application\IPresenter;
use Nette\Application\UI\Presenter;
use Nette\InvalidStateException;
use Nette\Security\User;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class LoginSubscriber implements EventSubscriberInterface
{

	/**
	 * @var IPresenter|null
	 */
	private $presenter;

	/**
	 * @var int
	 */
	private static $priority;

	/**
	 * @var RedirectConfig
	 */
	private $redirectConfig;

	public function __construct(RedirectConfig $redirectConfig, int $priority = 0)
	{
		$this->redirectConfig = $redirectConfig;
		self::$priority = $priority;
	}

	public function onPresenter(Application $application, IPresenter $presenter): void
	{
		$this->presenter = $presenter;
	}

	public function onLoggedIn(User $user): void
	{
		if ($this->presenter === null) {
			throw new InvalidStateException('Presenter not set');
		}
		if ($this->presenter instanceof Presenter && $this->presenter->getSession(OAuth2Presenter::SESSION_NAMESPACE)->authorizationRequest) {
			$this->presenter->redirect(...$this->redirectConfig->getApproveDestination());
		}
	}

	/**
	 * @return array
	 */
	public static function getSubscribedEvents(): array
	{
		return [
			Application::class . '::onPresenter',
			User::class . '::onLoggedIn' => [
				['onLoggedIn', self::$priority],
			],
		];
	}

}
