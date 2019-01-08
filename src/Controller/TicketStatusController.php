<?php

namespace App\Controller;

use App\Entity\TicketStatus;
use App\Form\TicketStatusType;
use App\Repository\TicketStatusRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

/**
 * @Route("/ticket/status")
 * @IsGranted("ROLE_ADMIN")
 */
class TicketStatusController extends AbstractController
{
    /**
     * @Route("/", name="ticket_status_index", methods="GET")
     */
    public function index(TicketStatusRepository $ticketStatusRepository): Response
    {
        return $this->render('ticket_status/index.html.twig', ['ticket_statuses' => $ticketStatusRepository->findAll()]);
    }

    /**
     * @Route("/new", name="ticket_status_new", methods="GET|POST")
     */
    public function new(Request $request): Response
    {
        $ticketStatus = new TicketStatus();
        $form = $this->createForm(TicketStatusType::class, $ticketStatus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em = $this->getDoctrine()->getManager();
            $em->persist($ticketStatus);
            $em->flush();

            return $this->redirectToRoute('ticket_status_index');
        }

        return $this->render('ticket_status/new.html.twig', [
            'ticket_status' => $ticketStatus,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="ticket_status_show", methods="GET")
     */
    public function show(TicketStatus $ticketStatus): Response
    {
        return $this->render('ticket_status/show.html.twig', ['ticket_status' => $ticketStatus]);
    }

    /**
     * @Route("/{id}/edit", name="ticket_status_edit", methods="GET|POST")
     */
    public function edit(Request $request, TicketStatus $ticketStatus): Response
    {
        $form = $this->createForm(TicketStatusType::class, $ticketStatus);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();

            return $this->redirectToRoute('ticket_status_index', ['id' => $ticketStatus->getId()]);
        }

        return $this->render('ticket_status/edit.html.twig', [
            'ticket_status' => $ticketStatus,
            'form' => $form->createView(),
        ]);
    }

    /**
     * @Route("/{id}", name="ticket_status_delete", methods="DELETE")
     */
    public function delete(Request $request, TicketStatus $ticketStatus): Response
    {
        if ($this->isCsrfTokenValid('delete'.$ticketStatus->getId(), $request->request->get('_token'))) {
            $em = $this->getDoctrine()->getManager();
            $em->remove($ticketStatus);
            $em->flush();
        }

        return $this->redirectToRoute('ticket_status_index');
    }
}
