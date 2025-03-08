<?php /** @noinspection PhpUnused */

namespace App\Controller;

use App\Form\PizzaSizeSettingType;
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
        private readonly DocumentManager $documentManager,
    ) {
        //
    }

    /**
     * @return Response
     */
    #[Route('/admin/settings', name: 'settings_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('setting/index.html.twig');
    }

    /**
     * @param Request $request
     * @param CurrencyService $currencyService
     * @return Response
     * @throws Throwable
     */
    #[Route('/admin/setting/currency', name: 'currency_setting', methods: ['GET', 'POST'])]
    public function editCurrency(Request $request, CurrencyService $currencyService): Response
    {
        $settings = $this->settingsRepository->findLastOrCreate();
        $currencies = $currencyService->getCurrencyChoices();

        $form = $this->createForm(SettingType::class, $settings, [
            'currencies' => $currencies,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $selectedCurrency = $form->get('currency')->getData();
            $settings->setCurrency($selectedCurrency);

            $country = $currencyService->getCountryByCurrency($selectedCurrency);
            if ($country !== null) {
                $settings->setCountry($country);
            }

            try {
                $this->documentManager->persist($settings);
                $this->documentManager->flush();
                $this->addFlash('success', 'Currency and country updated successfully!');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Error saving settings');
            }

            return $this->redirectToRoute('currency_setting');
        }

        return $this->render('setting/currency.html.twig', [
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
    #[Route('/admin/setting/stripe', name: 'stripe_setting', methods: ['GET', 'POST'])]
    public function editStripeKey(Request $request): Response
    {
        $settings = $this->settingsRepository->findLastOrCreate();

        $form = $this->createForm(StripeSettingType::class, $settings);
        $form->handleRequest($request);


        if ($form->isSubmitted() && $form->isValid()) {
            $newKey = $form->get('stripeSecretKey')->getData();

            if (!empty($newKey)) {
                $settings->setStripeSecretKey($newKey);
            }

            $this->documentManager->persist($settings);
            $this->documentManager->flush();

            $this->addFlash('success', 'Stripe key updated successfully!');

            return $this->redirectToRoute('stripe_setting');
        }

        return $this->render('setting/stripe.html.twig', [
            'form' => $form->createView()
        ]);
    }

    /**
     * @param Request $request
     * @return Response
     * @throws MongoDBException
     * @throws Throwable
     */
    #[Route('/admin/setting/pizza-sizes', name: 'pizza_size_setting', methods: ['GET', 'POST'])]
    public function editPizzaSizes(Request $request): Response
    {
        $settings = $this->settingsRepository->findLastOrCreate();

        $form = $this->createForm(PizzaSizeSettingType::class, $settings);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->documentManager->persist($settings);
            $this->documentManager->flush();

            $this->addFlash('success', 'Pizza size settings updated successfully!');
            return $this->redirectToRoute('pizza_size_setting');
        }

        return $this->render('setting/pizza_sizes.html.twig', [
            'form' => $form->createView()
        ]);
    }
}