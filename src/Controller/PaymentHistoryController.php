<?php

namespace App\Controller;

use App\Document\Order;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ADMIN')]
class PaymentHistoryController extends AbstractController
{
    /**
     * @return Response
     */
    #[Route('/admin/payment-history', name: 'admin-payment-history', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('payment/index.html.twig');
    }

    /**
     * API endpoint for fetching payment history for vue.
     *
     * @param DocumentManager $documentManager
     * @param Request $request
     * @return Response
     */
    #[Route('/admin/api/payment-history', name: 'admin-api-payment-history', methods: ['GET'])]
    public function api(DocumentManager $documentManager, Request $request): Response
    {
        $page = max(1, (int) $request->query->get('page', 1));
        $limit = min(100, (int) $request->query->get('limit', 20));
        $skip = ($page - 1) * $limit;

        $repository = $documentManager->getRepository(Order::class);
        $orders = $repository->findBy([], ['createdAt' => 'DESC'], $limit, $skip);

        $data = array_map(function (Order $order) {
            return [
                'id' => $order->getId(),
                'fullName' => $order->getFullName(),
                'phone' => $order->getPhone(),
                'totalPrice' => $order->getTotalPrice(),
                'status' => $order->getStatus()->value,
                'statusLabel' => $order->getStatus()->value,
                'createdAt' => $order->getCreatedAt()->format(DATE_ATOM),
            ];
        }, $orders);

        return $this->json($data);
    }

    /**
     * @param string $id
     * @param DocumentManager $documentManager
     * @return Response
     * @throws LockException
     * @throws MappingException
     */
    #[Route('/admin/payment-history/{id}', name: 'admin-payment-history-show', methods: ['GET'])]
    public function show(string $id, DocumentManager $documentManager): Response
    {
        $order = $documentManager->getRepository(Order::class)->find($id);

        if (!$order) {
            throw $this->createNotFoundException('Order ID not found: ' . $id);
        }

        $orderData = [
            'id' => $order->getId(),
            'fullName' => $order->getFullName(),
            'email' => $order->getEmail(),
            'phone' => $order->getPhone(),
            'address' => $order->getAddress(),
            'createdAt' => $order->getCreatedAt()->format('Y-m-d H:i:s'),
            'status' => $order->getStatus(),
            'pizzas' => $order->getPizzas(),
            'additions' => $order->getAdditions(),
            'totalPrice' => $order->getTotalPrice()
        ];

        return $this->render('payment/show.html.twig', [
            'order' => $orderData
        ]);
    }
}