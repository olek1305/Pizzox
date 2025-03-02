<?php

namespace App\Controller;

use App\Form\SettingType;
use App\Form\StripeSettingType;
use App\Repository\SettingRepository;
use App\Service\CurrencyService;
use Doctrine\ODM\MongoDB\DocumentManager;
use Doctrine\ODM\MongoDB\MongoDBException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Throwable;

class SettingController extends AbstractController
{
    public function __construct(
        private readonly SettingRepository $settingsRepository,
        private readonly DocumentManager $documentManager
    ) {}

    /**
     * @param Request $request
     * @param CurrencyService $currencyService
     * @return Response
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/admin/currency', name: 'admin_settings', methods: ['GET', 'POST'])]
    public function editCurrency(Request $request, CurrencyService $currencyService): Response
    {
        $settings = $this->settingsRepository->findOrCreateSetting();

        $currencies = $currencyService->getCurrencyChoices();

        $form = $this->createForm(SettingType::class, $settings, [
            'currencies' => $currencies,
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedCurrency = $form->get('currency')->getData();

            $country = $currencyService->getCountryByCurrency($selectedCurrency);
            if ($country !== null) {
                $settings->setCountry($country);
            }

            $this->documentManager->flush();

            $this->addFlash('success', 'Currency and country updated successfully!');
            return $this->redirectToRoute('admin_settings');
        }

        return $this->render('admin/currency.html.twig', [
            'form' => $form->createView(),
            'currencies' => $currencies,
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/admin/stripe', name: 'admin_stripe_settings', methods: ['GET', 'POST'])]
    public function editStripeKey(Request $request): Response
    {
        $settings = $this->settingsRepository->findOrCreateSetting();

        $existingKey = $settings->getStripeSecretKey();

        $form = $this->createForm(StripeSettingType::class, $settings);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $newKey = $form->get('stripeSecretKey')->getData();

            if (!empty($newKey)) {
                $settings->setStripeSecretKey($newKey);
            }

            $this->documentManager->flush();

            $this->addFlash('success', 'Stripe key updated successfully!');

            return $this->redirectToRoute('admin_stripe_settings');
        }

        return $this->render('admin/stripe.html.twig', [
            'form' => $form->createView()
        ]);
    }
}