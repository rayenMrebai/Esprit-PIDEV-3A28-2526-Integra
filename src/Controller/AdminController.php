<?php

namespace App\Controller;

use App\Entity\UserAccount;
use App\Form\UserEditFormType;
use App\Form\RegistrationFormType;
use App\Repository\UserAccountRepository;
use Doctrine\ORM\EntityManagerInterface;
use Dompdf\Dompdf;
use Dompdf\Options;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use App\Service\HuggingFaceService;
use Symfony\Component\HttpFoundation\JsonResponse;

#[Route('/admin')]
class AdminController extends AbstractController
{
    #[Route('/user/{id}/ai-advice', name: 'admin_user_ai_advice')]
    #[IsGranted('ROLE_ADMIN')]
    public function aiAdvice(UserAccount $user, HuggingFaceService $ai): JsonResponse
    {
        try {
            $lastLogin = $user->getLastLogin();
            $lastLoginStr = $lastLogin ? $lastLogin->format('d/m/Y') : 'never';

            $prompt = sprintf(
                "As an HR assistant, give a short actionable recommendation (one sentence) for a user with role=%s, last login=%s, active=%s.",
                $user->getRole(),
                $lastLoginStr,
                $user->getIsActive() ? 'yes' : 'no'
            );

            $advice = $ai->generateAdvice($prompt);
            return $this->json(['advice' => $advice]);
        } catch (\Exception $e) {
            // Return the exact error message for debugging
            return $this->json(['advice' => 'AI error: ' . $e->getMessage()], 500);
        }
    }
    #[Route('/dashboard', name: 'app_dashboard')]
    #[IsGranted('ROLE_MANAGER')]
    public function dashboard(UserAccountRepository $repo, HttpClientInterface $httpClient): Response
    {
        // Auto‑deactivate users inactive for >3 days
        $deactivatedCount = $repo->deactivateInactiveUsers();
        if ($deactivatedCount > 0) {
            $this->addFlash('info', "$deactivatedCount user(s) were deactivated due to inactivity.");
        }

        $totalUsers = $repo->count([]);
        $activeUsers = $repo->count(['isActive' => true]);
        $stats = $repo->countActiveVsInactive();
        $riskUsers = $repo->findAllWithRisk();   // includes all users

        // Weather data (OpenWeatherMap)
        $weather = null;
        try {
            $apiKey = $_ENV['WEATHER_API_KEY'] ?? '';
            if ($apiKey) {
                $url = "https://api.openweathermap.org/data/2.5/weather?q=Tunis,tn&appid={$apiKey}&units=metric&lang=fr";
                $response = $httpClient->request('GET', $url);
                $data = $response->toArray();
                if ($response->getStatusCode() === 200) {
                    $weather = $data;
                }
            }
        } catch (\Exception $e) {
            $weather = null;
        }

        return $this->render('admin/dashboard.html.twig', [
            'totalUsers' => $totalUsers,
            'activeUsers' => $activeUsers,
            'stats' => $stats,
            'weather' => $weather,
            'riskUsers' => $riskUsers,
        ]);
    }

    #[Route('/users', name: 'admin_user_list')]
    #[IsGranted('ROLE_MANAGER')]
    public function list(UserAccountRepository $repo, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $role = $request->query->get('role', '');
        $users = $repo->findByRoleAndSearch($role ?: null, $search ?: null);
        return $this->render('admin/user_list.html.twig', [
            'users' => $users,
            'search' => $search,
            'selectedRole' => $role,
        ]);
    }

    #[Route('/user/{id}', name: 'admin_user_show')]
    #[IsGranted('ROLE_MANAGER')]
    public function show(UserAccount $user): Response
    {
        return $this->render('admin/user_show.html.twig', ['user' => $user]);
    }

    #[Route('/user/{id}/edit', name: 'admin_user_edit')]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(UserAccount $user, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(UserEditFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            $this->addFlash('success', 'User updated.');
            return $this->redirectToRoute('admin_user_list');
        }

        return $this->render('admin/user_edit.html.twig', [
            'form' => $form->createView(),
            'user' => $user
        ]);
    }

    #[Route('/add-user', name: 'admin_add_user')]
    #[IsGranted('ROLE_ADMIN')]
    public function add(Request $request, UserPasswordHasherInterface $passwordHasher, EntityManagerInterface $em): Response
    {
        $user = new UserAccount();
        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $user->setPasswordHash($passwordHasher->hashPassword($user, $form->get('plainPassword')->getData()));
            $em->persist($user);
            $em->flush();
            $this->addFlash('success', 'User added successfully.');
            return $this->redirectToRoute('admin_user_list');
        }

        return $this->render('admin/add_user.html.twig', ['form' => $form->createView()]);
    }

    #[Route('/user/{id}/toggle', name: 'admin_user_toggle')]
    #[IsGranted('ROLE_MANAGER')]
    public function toggleActive(UserAccount $user, EntityManagerInterface $em): Response
    {
        $user->setIsActive(!$user->getIsActive());
        $em->flush();
        $this->addFlash('success', 'User status changed.');
        return $this->redirectToRoute('admin_user_list');
    }

    #[Route('/user/{id}/delete', name: 'admin_user_delete')]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(UserAccount $user, EntityManagerInterface $em, Request $request): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getUserId(), $request->request->get('_token'))) {
            $em->remove($user);
            $em->flush();
            $this->addFlash('success', 'User deleted.');
        } else {
            $this->addFlash('error', 'Invalid CSRF token.');
        }
        return $this->redirectToRoute('admin_user_list');
    }

    #[Route('/users/export-pdf', name: 'admin_export_pdf')]
    #[IsGranted('ROLE_ADMIN')]
    public function exportPdf(UserAccountRepository $repo, Request $request): Response
    {
        $search = $request->query->get('search', '');
        $role = $request->query->get('role', '');
        $users = $repo->findByRoleAndSearch($role ?: null, $search ?: null);

        $html = $this->renderView('admin/user_list_pdf.html.twig', [
            'users' => $users,
            'generatedAt' => new \DateTime(),
        ]);

        $options = new Options();
        $options->set('defaultFont', 'Arial');
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml($html);
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return new Response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="users_list.pdf"',
        ]);
    }
}