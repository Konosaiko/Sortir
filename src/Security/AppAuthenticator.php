<?php

namespace App\Security;

use App\Repository\UserRepository;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\CustomUserMessageAuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractLoginFormAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\RememberMeBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Credentials\PasswordCredentials;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\SecurityRequestAttributes;
use Symfony\Component\Security\Http\Util\TargetPathTrait;

class AppAuthenticator extends AbstractLoginFormAuthenticator
{
    use TargetPathTrait;

    public const LOGIN_ROUTE = 'app_login';
    private UserRepository $userRepository;

    public function __construct(private UrlGeneratorInterface $urlGenerator, UserRepository $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    public function authenticate(Request $request): Passport
    {
        $login = $request->request->get('login', '');

        $user = $this->userRepository->findOneByEmailOrUsername($login);
        if (!$user) {
            // Lancer une exception d'authentification si l'utilisateur n'est pas trouvé
            throw new CustomUserMessageAuthenticationException('Identifiants invalides.');
        }

        return new Passport(
            new UserBadge($login, function($userIdentifier) {
                // La fonction anonyme est utilisée pour charger l'utilisateur par email ou pseudo
                return $this->userRepository->findOneByEmailOrUsername($userIdentifier);
            }),
            new PasswordCredentials($request->request->get('password', ''))
        // Vous pouvez ajouter d'autres badges si nécessaire ici
        );
    }

    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        if ($targetPath = $this->getTargetPath($request->getSession(), $firewallName)) {
            return new RedirectResponse($targetPath);
        }


        return new RedirectResponse($this->urlGenerator->generate('app_home'));

    }

    protected function getLoginUrl(Request $request): string
    {
        return $this->urlGenerator->generate(self::LOGIN_ROUTE);
    }
}
