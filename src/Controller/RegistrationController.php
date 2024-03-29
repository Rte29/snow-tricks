<?php

namespace App\Controller;

use App\Entity\User;
use App\Entity\Figure;
use App\Form\FigureType;
use App\Entity\ResetPassword;
use App\Form\ForgotPasswordType;
use App\Form\ResetPasswordType;

use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use App\Security\UserAuthenticator;
use Symfony\Component\Mime\Address;
use App\Repository\FigureRepository;
use App\Repository\CategoryRepository;
use Doctrine\ORM\EntityManagerInterface;
use phpDocumentor\Reflection\Types\Nullable;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\AsciiSlugger;
use Symfony\Contracts\Translation\TranslatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Mailer\Exception\TransportExceptionInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Http\Authentication\UserAuthenticatorInterface;

class RegistrationController extends AbstractController
{
    protected $mailer;
    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * @throws TransportExceptionInterface
     */
    #[Route('/utilisateur/inscription', name: 'app_register')]
    public function registerUser(Request $request, MailerInterface $mailer, UserPasswordHasherInterface $userPasswordHasher, UserAuthenticatorInterface $userAuthenticator, UserAuthenticator $authenticator, EntityManagerInterface $entityManager): Response
    {
        $user = new User();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // encode the plain password
            $user->setPassword(
                $userPasswordHasher->hashPassword(
                    $user,
                    $form->get('plainPassword')->getData()
                )
            );
            $user->setActivated(false);

            $token = bin2hex(random_bytes(16));

            $user->setToken($token);

            $entityManager->persist($user);
            $entityManager->flush();

            $mail = (new Email())
                ->from("snowtricksinfo@gmail.com")
                ->to($user->getEmail())
                ->text("Voici votre lien de verification de mail https://127.0.0.1:8000/utilisateur/activation/" . $token)
                ->subject("Activation de compte sur SnowTricks pour " . $user->getUserName());


            $mailer->send($mail);


            // do anything else you need here, like send an email

            /*return $userAuthenticator->authenticateUser(
                $user,
                $authenticator,
                $request
            );*/

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/utilisateur/activation/{token}', name: 'activate')]
    public function activateToken($token, EntityManagerInterface $manager, UserRepository $userRepo): Response
    {
        $user = $userRepo->findOneBy(['token' => $token]);
        if ($user == null) {
            $this->addFlash('error', 'Votre compte est deja activé');
            return $this->redirectToRoute('app_home');
        }

        $user->setActivated('1');
        $token = bin2hex(random_bytes(16));
        $user->setToken($token);


        $manager->persist($user);
        $manager->flush();
        $this->addFlash('success', 'Votre compte est activé');


        return $this->redirectToRoute('app_home');
    }

    #[Route('/utilisateur/mot-de-passe-oublie', name: 'app_forgot')]
    public function forgotPassword(EntityManagerInterface $manager, MailerInterface $mailer, Request $request, UserRepository $userRepo): Response
    {

        if ($request->getMethod() == 'POST') {

            $data = $request->request->all();
            $email = $data['email'];

            $user = $userRepo->findOneBy(['email' => $email]);

            if (!$user) {

                $this->addFlash(
                    'error',
                    'Cette adresse e-mail n\'existe pas!'
                );
            } else {

                $token = bin2hex(random_bytes(16));
                $user->setToken($token);

                $manager->persist($user);
                $manager->flush();

                $mail = (new Email())
                    ->from("er.gouez@gmail.com")
                    ->to($user->getEmail())
                    ->text("Voici votre lien de verification de mail https://127.0.0.1:8000/nouveau-mot-de-passe/" . $token)
                    ->subject("Activation de compte sur SnowTricks pour " . $user->getUserName());


                $mailer->send($mail);

                return $this->redirectToRoute('app_login');
            }
        }
        return $this->render('security/forgot.html.twig', []);
    }

    #[Route('/utilisateur/nouveau-mot-de-passe/{token}', name: 'app_reset')]
    public function resetPassword(EntityManagerInterface $manager, $token, UserPasswordHasherInterface $passwordHasher, MailerInterface $mailer, Request $request, UserRepository $userRepo): Response
    {

        $resetPassword = new User();
        $user = $userRepo->findOneBy(['token' => $token]);

        if ($user) {

            $form = $this->createForm(ResetPasswordType::class, $resetPassword);
            $form->handleRequest($request);

            if ($form->isSubmitted() && $form->isValid()) {
                //TODO set token a null
                $token = bin2hex(random_bytes(16));
                $user->setToken($token)
                    ->setPassword($passwordHasher->hashPassword($user, $resetPassword->getPassword()));

                $manager->persist($user);
                $manager->flush();
                $this->addFlash(
                    'success',
                    'Votre mot de passe a bien été modifié. Vous pouvez l\'utiliser pour vous connecter'
                );

                return $this->redirectToRoute('app_home');
            }
        } else {

            $this->addFlash(
                'error',
                'Le lien de réinitialisation du mot de passe n\'est plus valide'
            );

            return $this->redirectToRoute('app_home');
        }
        return $this->render('registration/reset_password.html.twig', [
            'form' => $form->createView(),
        ]);
    }
}
