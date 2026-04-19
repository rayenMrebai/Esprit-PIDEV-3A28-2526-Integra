<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\UserSettingRepository::class)]
#[ORM\Table(name: 'user_settings')]
class UserSetting
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer', name: 'settingsId')]
    private ?int $settingsId = null;

    #[ORM\OneToOne(targetEntity: UserAccount::class, inversedBy: 'userSetting')]
    #[ORM\JoinColumn(name: 'userId', referencedColumnName: 'userId', unique: true)]
    private ?UserAccount $userAccount = null;

    #[ORM\Column(type: 'string', nullable: true, name: 'theme')]
    private ?string $theme = null;

    #[ORM\Column(type: 'string', nullable: true, name: 'language')]
    private ?string $language = null;

    #[ORM\Column(type: 'string', nullable: true, name: 'defaultModule')]
    private ?string $defaultModule = null;

    #[ORM\Column(type: 'boolean', nullable: true, name: 'notificationsEnabled')]
    private ?bool $notificationsEnabled = null;

    #[ORM\Column(type: 'text', nullable: true, name: 'dashboardLayout')]
    private ?string $dashboardLayout = null;

    #[ORM\Column(type: 'text', nullable: true, name: 'accessPreferences')]
    private ?string $accessPreferences = null;

    // Getters and setters
    public function getSettingsId(): ?int
    {
        return $this->settingsId;
    }

    public function setSettingsId(int $settingsId): self
    {
        $this->settingsId = $settingsId;
        return $this;
    }

    public function getUserAccount(): ?UserAccount
    {
        return $this->userAccount;
    }

    public function setUserAccount(?UserAccount $userAccount): self
    {
        $this->userAccount = $userAccount;
        return $this;
    }

    public function getTheme(): ?string
    {
        return $this->theme;
    }

    public function setTheme(?string $theme): self
    {
        $this->theme = $theme;
        return $this;
    }

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;
        return $this;
    }

    public function getDefaultModule(): ?string
    {
        return $this->defaultModule;
    }

    public function setDefaultModule(?string $defaultModule): self
    {
        $this->defaultModule = $defaultModule;
        return $this;
    }

    public function isNotificationsEnabled(): ?bool
    {
        return $this->notificationsEnabled;
    }

    public function setNotificationsEnabled(?bool $notificationsEnabled): self
    {
        $this->notificationsEnabled = $notificationsEnabled;
        return $this;
    }

    public function getDashboardLayout(): ?string
    {
        return $this->dashboardLayout;
    }

    public function setDashboardLayout(?string $dashboardLayout): self
    {
        $this->dashboardLayout = $dashboardLayout;
        return $this;
    }

    public function getAccessPreferences(): ?string
    {
        return $this->accessPreferences;
    }

    public function setAccessPreferences(?string $accessPreferences): self
    {
        $this->accessPreferences = $accessPreferences;
        return $this;
    }
}