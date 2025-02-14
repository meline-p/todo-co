<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\UserType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class UserController extends AbstractController
{
    #[Route('/users', name: 'user_list', methods:'get')]
    #[IsGranted('ROLE_ADMIN')]
    public function list(UserRepository $userRepository,  TagAwareCacheInterface $cachePool): Response
    {
        $idCache = 'user_list';

        $users = $cachePool->get($idCache, function (ItemInterface $item) use ($userRepository, $idCache) {
            $item->expiresAfter(3600);
            $item->tag($idCache);
            return $userRepository->findAll();
        });

        return $this->render('user/list.html.twig', [
            'users' =>$users
        ]);
    }

    #[Route('/users/create', name: 'user_create', methods:['get','post'])]
    #[IsGranted('ROLE_ADMIN')]
    public function create(Request $request, EntityManagerInterface $em, UserPasswordHasherInterface $passwordHasher, TagAwareCacheInterface $cachePool): Response
    {
        $user = new User();
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => false,
            'is_admin' => true
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPassword(
                $passwordHasher->hashPassword(
                    $user,
                    $form->get('password')->getData()
                )
            );
            $user->setRoles($form->get('roles')->getData());
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', sprintf('L\'utilisateur %s a bien été ajouté.', $user->getUsername()));

            $cachePool->invalidateTags(['user_list']);

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/create.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/users/{id}/edit', name: 'user_edit', methods:['get','post'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(User $user, Request $request, EntityManagerInterface $em, TagAwareCacheInterface $cachePool): Response
    {
        $form = $this->createForm(UserType::class, $user, [
            'is_edit' => true,
            'is_admin' => true
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->persist($user);
            $em->flush();

            $this->addFlash('success', sprintf('L\'utilisateur %s a bien été modifié', $user->getUsername()));

            $cachePool->invalidateTags(['user_list']);

            return $this->redirectToRoute('user_list');
        }

        return $this->render('user/edit.html.twig', ['form' => $form->createView(), 'user' => $user]);
    }
}
