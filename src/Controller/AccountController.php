<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\AccountType;
use App\Entity\PasswordUpdate;
use App\Form\RegistrationType;
use App\Form\PasswordUpdateType;
use Symfony\Component\Form\FormError;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AccountController extends AbstractController
{
    /**
     * @Route("/login", name="account_login")
     * Permet d'afficher et de gérer le formulaire de conneixon
     * @return Response
     */
    public function login(AuthenticationUtils $utils): Response
    {
        $error = $utils->getLastAuthenticationError();
        $username = $utils->getLastUsername();

        return $this->render('account/login.html.twig', [
            'hasError' => $error !== null,
            'username' => $username
        ]);
    }


    /**
     * Permet de se déconnecter
     * @Route("/logout", name="account_logout")
     * @return void
     */
    public function logout()
    {
        //rien
    }

    /**
     * Permet d'afficher le formulaire d'inscription
     *  @Route("/register", name="account_register")
     * @return Response
     */
    public function register(Request $request, EntityManagerInterface $manager, UserPasswordHasherInterface $encoder)
    {
        $user = new User();
        $form = $this->createForm(RegistrationType::class, $user);
        $form->handleRequest($request);

        if($form->isSubmitted() && $form->isValid())
        {
            $hash = $encoder->hashPassword($user, $user->getHash());
            $user->setHash($hash);

            $manager->persist($user);
            $manager->flush();

            $this->addFlash(
                'success',
                "Votre compte a bien été enregistré ! Vous pouvez maintenant vous connecter"
            );
            return $this->redirectToRoute('account_login');
        }

        return $this->render('account/registration.html.twig', [
            'form' =>$form->createView()
        ]);
    }

    /**
     * Permet d'afficher et de gérer le formulaire de modification de profil
     * @Route("/account/profile", name="account_profile")
     * @IsGranted("ROLE_USER")
     * @return Response
     */
    public function profile(Request $request, EntityManagerInterface $manager)
    {
        $user = $this->getUser();
        $form = $this->createForm(AccountType::class, $user);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $manager->persist($user);
            $manager->flush();
            $this->addFlash(
                'success', 
                "Les données du profil ont été enregistrée avec succès"
            );
        }

        return $this->render('account/profile.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * Permet de modifier le MDP
     * @Route("/account/password-update", name="account_password")
     * @IsGranted("ROLE_USER")
     * @return Response
     */
    public function updatePassword(Request $request, UserPasswordHasherInterface $encoder, EntityManagerInterface $manager)
    {
        $passwordUpdate = new PasswordUpdate;
        $form = $this->createForm(PasswordUpdateType::class, $passwordUpdate);

        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid())
        {
            $user = $this->getUser();
            if(!password_verify($passwordUpdate->getOldPassword(), $user->getHash()))
            {
                //Gestion des erreurs
                $form->get('oldPassword')->addError(new FormError("Le mot de passe que vous avez tapé n'est pas votre mot de passe actuel !"));
            } else{
                $newPassword = $passwordUpdate->getNewPassword();
                $hash = $encoder->hashPassword($user,$newPassword);
                $user->setHash($hash);

                $manager->persist($user);
                $manager->flush($user);
                $this->addFlash(
                    'success',
                    "Votre mot de passe a bien été modifié !"
                );
                return $this->redirectToRoute('homepage');
            }
        }

        return $this->render('account/password.html.twig', [
            'form' => $form->createView()   
        ]);
    }

    /**
     * Permet d'afficher le profil de l'utilisateur connecté
     * @Route("/account", name="account_index")
     * @IsGranted("ROLE_USER")
     * @return Response
     */
    public function myAccount()
    {
        return $this->render('user/index.html.twig', [
            'user' => $this->getUser()
        ]);
    }
}
