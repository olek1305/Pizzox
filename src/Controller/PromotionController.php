<?php

namespace App\Controller;

use App\Document\Addition;
use App\Document\Pizza;
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
use Symfony\Contracts\Cache\CacheInterface;
use Throwable;

#[IsGranted('ROLE_ADMIN')]
class PromotionController extends AbstractController
{
    /**
     * @param DocumentManager $documentManager
     * @param CacheInterface $cache
     */
    public function __construct(
        private readonly DocumentManager $documentManager,
        private readonly CacheInterface $cache
    ) {
        //
    }

    /**
     * @return Response
     * @throws LockException
     * @throws MappingException
     */
    #[Route('/admin/promotions', name: 'promotions_index', methods: ['GET'])]
    public function index(): Response
    {
        $promotions = $this->documentManager->getRepository(Promotion::class)->findAll();

        $items = [];
        foreach ($promotions as $promotion) {

            if ($promotion->getItemType() === 'pizza') {
                $pizza = $this->documentManager->getRepository(Pizza::class)->find($promotion->getItemId());
                if ($pizza) {
                    $items[$promotion->getItemId()] = $pizza->getName();
                }
            } else if ($promotion->getItemType() === 'addition') {
                $addition = $this->documentManager->getRepository(Addition::class)->find($promotion->getItemId());
                if ($addition) {
                    $items[$promotion->getItemId()] = $addition->getName();
                }
            }
        }

        return $this->render('setting/promotion.html.twig', [
            'promotions' => $promotions,
            'items' => $items
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
        $expiresAt->setTime(0, 0);

        $code = uniqid($type . '_', true);
        $existingPromotion = $this->documentManager->getRepository(Promotion::class)->findOneBy(['code' => $code]);
        if ($existingPromotion) {
            $this->addFlash('error', 'A promotion with the specified code already exists. Choose a different code.');
            return $this->redirectToRoute($type . '_edit', ['id' => $itemId]);
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

        $this->cache->delete('pizzas_data');

        return $this->redirectToRoute($type . '_edit', ['id' => $itemId]);
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

        return $this->redirectToRoute('promotions_index');
    }

    /**
     * @param string $id
     * @return Response
     * @throws LockException
     * @throws MappingException
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/promotion/delete/{id}', name: 'promotion_delete', methods: ['POST'])]
    public function delete(string $id): Response
    {
        $promotion = $this->documentManager->getRepository(Promotion::class)->find($id);

        if (!$promotion) {
            throw $this->createNotFoundException('Promotion not found');
        }

        $this->documentManager->remove($promotion);
        $this->documentManager->flush();

        $this->addFlash('success', 'Promotion deleted successfully');

        return $this->redirectToRoute('promotions_index');
    }

}