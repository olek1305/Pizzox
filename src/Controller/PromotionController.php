<?php

namespace App\Controller;

use App\Document\Promotion;
use DateTime;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\LockException;
use Doctrine\ODM\MongoDB\Mapping\MappingException;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Throwable;

#[IsGranted('ROLE_ADMIN')]
class PromotionController extends AbstractController
{
    /**
     * @param DocumentManager $documentManager
     */
    public function __construct(
        private readonly DocumentManager $documentManager
    ) {
        //
    }

    /**
     * @return Response
     */
    #[Route('/admin/promotions', name: 'admin_promotions', methods: ['GET'])]
    public function index(): Response
    {
        $promotions = $this->documentManager->getRepository(Promotion::class)->findAll();

        return $this->render('admin/promotion/index.html.twig', [
            'promotions' => $promotions
        ]);
    }

    /**
     * @param Request $request
     * @param string $type
     * @param string $itemId
     * @return Response
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/promotion/create/{type}/{itemId}', name: 'promotion_create', methods: ['POST'])]
    public function create(Request $request, string $type, string $itemId): Response
    {
        $usageLimit = (int) $request->request->get('usage_limit');
        $discountValue = (float) $request->request->get('discount_value');
        $discountType = $request->request->get('discount_type'); // 'percentage' or 'fixed'
        $expiryDays = (int) $request->request->get('expiry_days', 7);

        $expiresAt = new DateTime();
        $expiresAt->modify("+$expiryDays days");

        $code = uniqid($type . '_', true);
        $existingPromotion = $this->documentManager->getRepository(Promotion::class)->findOneBy(['code' => $code]);
        if ($existingPromotion) {
            $this->addFlash('error', 'A promotion with the specified code already exists. Choose a different code.');
            return $this->redirectToRoute($type . '_show', ['id' => $itemId]);
        }

        $promotion = new Promotion();
        $promotion->setCode(uniqid($type . '_', true))
            ->setType($discountType)
            ->setDiscount($discountValue)
            ->setUsageLimit($usageLimit)
            ->setExpiresAt($expiresAt)
            ->setActive(true);

        // Store reference to the item (pizza or addition)
        $promotion->setItemType($type)
            ->setItemId($itemId);

        $this->documentManager->persist($promotion);
        $this->documentManager->flush();

        $this->addFlash('success', 'Promotion created successfully! Valid until: ' . $expiresAt->format('Y-m-d'));

        return $this->redirectToRoute($type . '_show', ['id' => $itemId]);
    }

    /**
     * @param string $id
     * @return Response
     * @throws MongoDBException
     * @throws Throwable
     * @throws LockException
     * @throws MappingException
     */
    #[Route('/promotion/toggle/{id}', name: 'promotion_toggle', methods: ['POST'])]
    public function toggleActive(string $id): Response
    {
        $promotion = $this->documentManager->getRepository(Promotion::class)->find($id);

        if (!$promotion) {
            throw $this->createNotFoundException('Promotion not found');
        }

        $promotion->setActive(!$promotion->isActive());
        $this->documentManager->flush();

        $this->addFlash('success', 'Promotion status updated successfully');

        return $this->redirectToRoute('promotion_index');
    }
}