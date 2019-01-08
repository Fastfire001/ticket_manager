<?php

namespace App\Controller;

use App\Form\UserType;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Registry;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Repository\UserRepository;
use Symfony\Bridge\Doctrine\RegistryInterface;

class RegistrationController extends AbstractController
{
    /**
     * @Route("/register", name="user_registration")
     */
    public function register(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        $user = new User();
        $userRepository = $this->getDoctrine()->getManager()->getRepository(User::class);
        $form = $this->createForm(UserType::class, $user);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid() && empty($userRepository->findBy(['username' => $user->getUsername()])) && empty($userRepository->findBy(['email' => $user->getEmail()]))) {
            $password = $passwordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);

            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($user);
            $entityManager->flush();

            return $this->redirectToRoute('ticket_index');
        }

        return $this->render('registration/register.html.twig', array('form' => $form->createView())
        );
    }
}