<?php

namespace App\Controller;

use App\Entity\Task;
use App\Form\TaskType;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Cache\CacheItemPoolInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

class TaskController extends AbstractController
{
    protected $cacheTags = [];
    private $cachePool ;

    public function __construct(CacheItemPoolInterface $cachePool)
    {
        $this->cachePool = $cachePool;
    }

    #[Route('/tasks', name: 'task_list', methods:["get", "post"])]
    public function list(Request $request, TaskRepository $taskRepository, PaginatorInterface $paginator, TagAwareCacheInterface $cachePool): Response
    {
        $filter = $request->query->get('status', 'all');
        $page = $request->query->getInt('page', 1);
        $idCache = 'task_list_' . $filter . '_page_' . $page;

        $tasks = $cachePool->get($idCache, function (ItemInterface $item) use ($filter, $taskRepository, $paginator, $request, $idCache) {
            $item->expiresAfter(3600);
            $item->tag($idCache);

            $queryBuilder = $taskRepository->createQueryBuilder('t');

            if ($filter === 'is_done') {
                $queryBuilder->where('t.isDone = :status')->setParameter('status', true);
            } elseif ($filter === 'in_progress') {
                $queryBuilder->where('t.isDone = :status')->setParameter('status', false);
            }

            $queryBuilder->orderBy('t.isDone', 'ASC')
                ->addOrderBy('t.dueDate', 'DESC')
                ->addOrderBy('t.createdAt', 'DESC');

            return $paginator->paginate(
                $queryBuilder,
                $request->query->getInt('page', 1),
                6
            );
        });

        return $this->render('task/list.html.twig', [
            'tasks' => $tasks,
            'filter' => $filter,
        ]);
    }

    #[Route('/tasks/create', name: 'task_create', methods:["get", "post"])]
    public function create(Request $request, EntityManagerInterface $em, Security $security): Response
    {
        $task = new Task();
        $currentUser = $security->getUser();

        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {

            $task->setAuthor($currentUser);
            $task->setIsDone(false);
            $em->persist($task);
            $em->flush();

            $this->addFlash('success', sprintf('La tâche %s a été bien été ajoutée.', $task->getTitle()));

            $this->cachePool->clear();

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/create.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/tasks/{id}/edit', name: 'task_edit', methods:["get", "post"])]
    public function edit(Task $task, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(TaskType::class, $task);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->addFlash('success', sprintf('La tâche %s a bien été modifiée.', $task->getTitle()));

            $this->cachePool->clear();

            return $this->redirectToRoute('task_list');
        }

        return $this->render('task/edit.html.twig', [
            'form' => $form->createView(),
            'task' => $task,
        ]);
    }

    #[Route('/tasks/{id}/toggle', name: 'task_toggle', methods:'get')]
    public function toggle(Task $task,EntityManagerInterface $em): Response
    {
        $task->toggle(!$task->isDone());
        $em->flush();

        if($task->isDone()) {
            $this->addFlash('success', sprintf('La tâche %s a été marquée avec succès comme terminée.', $task->getTitle()));
        } else {
            $this->addFlash('success', sprintf('La tâche %s a été marquée avec succès comme en cours.', $task->getTitle()));
        }

        $this->cachePool->clear();

        return $this->redirectToRoute('task_list');
    }

    #[Route('/tasks/{id}/delete', name: 'task_delete', methods:"get")]
    #[IsGranted('TASK_DELETE', 'task')]
    public function delete(Task $task, EntityManagerInterface $em): Response
    {
        $em->remove($task);
        $em->flush();

        $this->addFlash('success', 'La tâche a bien été supprimée.');

        $this->cachePool->clear();

        return $this->redirectToRoute('task_list');
    }
}
