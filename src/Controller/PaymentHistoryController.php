<?php

namespace App\Controller;

use App\Document\Order;
use Doctrine\ODM\MongoDB\DocumentManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class PaymentHistoryController extends AbstractController
{
    #[Route('/admin/payment-history', name: 'admin-payment-history', methods: ['GET'])]
    public function index(DocumentManager $documentManager): Response
    {
        $orders = $documentManager
            ->getRepository(Order::class)
            ->findBy([], ['createdAt' => 'DESC']);

        return $this->render('payment/index.html.twig', [
            'orders' => $orders,
        ]);
    }

    #[Route('/admin/payment-history/{id}', name: 'admin-payment-history-show', methods: ['GET'])]
    public function show(string $id, DocumentManager $documentManager): Response
    {
        $order = $documentManager->getRepository(Order::class)->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Nie znaleziono zamówienia o ID: ' . $id);
        }

        return $this->render('payment/show.html.twig', [
            'order' => $order
        ]);
    }
}