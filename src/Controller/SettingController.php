<?php

namespace App\Controller;

use App\Form\SettingType;
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
}