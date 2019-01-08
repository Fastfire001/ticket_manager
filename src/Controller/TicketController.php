<?php

namespace App\Controller;

use App\Entity\Message;
use App\Entity\Ticket;
use App\Entity\User;
use App\Form\MessageType;
use App\Form\TicketAdminType;
use App\Form\TicketType;
use App\Repository\TicketRepository;
use App\Repository\TicketStatusRepository;
use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Component\Validator\Constraints\Date;

/**
 * @Route("/ticket")
 * @IsGranted("ROLE_USER")
 */
class TicketController extends AbstractController
{

    public $defaultOpenStatusId = 1;
    /**
     * @Route("/", name="ticket_index", methods="GET")
     */
    public function index(TicketRepository $ticketRepository): Response
    {
        return $this->render('ticket/index.html.twig', ['tickets' => $this->getUser()->getTicketss(), 'h1' => 'My Ticket']);
    }

    /**
     * @Route("/all", name="ticket_all", methods="GET")
     */
    public function allTicket(TicketRepository $ticketRepository): Response
    {
        return $this->render('ticket/index.html.twig', ['tickets' => $ticketRepository->findAll(), 'h1' => 'Ticket Administration']);
    }

    /**
     * @Route("/my-assign", name="ticket_my_assign", methods="GET")
     */
    public function assignTicket(TicketRepository $ticketRepository): Response
    {
        return $this->render('ticket/index.html.twig', ['tickets' => $this->getUser()->getAssignTo(), 'h1' => 'My Assign Ticket']);
    }

    /**
     * @Route("/{id}/remove-user/{user_id}", name="ticket_remove_user", methods="GET|POST")
     */
    public function removeUser(Request $request, TicketRepository $ticketRepository, UserRepository $userRepository): Response
    {
        $ticketId = $request->get('id');
        $userId = $request->get('user_id');
        $ticket = $ticketRepository->find($ticketId)->removeAssignTo($userRepository->find($userId));
        $em = $this->getDoctrine()->getManager();
        $em->persist($ticket);
        $em->flush();
        return $this->redirectToRoute('ticket_show', ['id' => $ticketId]);
    }
    /**
     * @Route("/new", name="ticket_new", methods="GET|POST")
     */
    public function new(Request $request, TicketStatusRepository $ticketStatusRepository): Response
    {
        $ticket = new Ticket();
        $form = $this->createForm(TicketType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $statusId = $this->defaultOpenStatusId;
            $status = $ticketStatusRepository->find($this->defaultOpenStatusId);
            $ticket->setDate(new \DateTime())
                ->setUserId($this->getUser())
                ->setStatus($status);
            $em = $this->getDoctrine()->getManager();
            $em->persist($ticket);
            $em->flush();

            return $this->redirectToRoute('ticket_index');
        }

        return $this->render('ticket/new.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="ticket_show", methods="GET|POST")
     */
    public function show(Request $request, Ticket $ticket): Response
    {

        //check right
        if (!(in_array($this->getUser(), $ticket->getAssignTo()->getValues()) || $this->getUser() == $ticket->getUserId()) && !in_array('ROLE_ADMIN', $this->getUser()->getRoles())) {
            return $this->redirectToRoute('ticket_index');
        }


        $message = new Message();
        $form = $this->createForm(MessageType::class, $message);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid() && $ticket->getStatus()->getIsOpen()) {
            $message
                ->setUserId($this->getUser())
                ->setDate(new \DateTime())
                ->setTicketId($ticket);
            $em = $this->getDoctrine()->getManager();
            $em->persist($message);
            $em->flush();
            $form = $this->createForm(MessageType::class);
        }
        $twigParam = [
            'ticket' => $ticket,
            'messageForm' => $form->createView(),
            'messages' => $ticket->getMessages()
        ];
        return $this->render('ticket/show.html.twig', $twigParam);
    }

    /**
     * @Route("/{id}/edit", name="ticket_edit", methods="GET|POST")
     * @IsGranted("ROLE_ADMIN")
     */
    public function edit(Request $request, Ticket $ticket): Response
    {
        $form = $this->createForm(TicketAdminType::class, $ticket);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('ticket_index', ['id' => $ticket->getId()]);
        }

        return $this->render('ticket/edit.html.twig', [
            'ticket' => $ticket,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="ticket_delete", methods="DELETE")
     * @IsGranted("ROLE_ADMIN")
     */
    public function delete(Request $request, Ticket $ticket): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ticket->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($ticket);
            $em->flush();
        }

        return $this->redirectToRoute('ticket_index');
    }
}
