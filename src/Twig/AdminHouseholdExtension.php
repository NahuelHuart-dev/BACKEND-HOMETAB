<?php

namespace App\Twig;

use App\Repository\HouseholdRepository;
use Twig\Extension\AbstractExtension;
use Twig\TwigFunction;

class AdminHouseholdExtension extends AbstractExtension
{
    public function __construct(private HouseholdRepository $householdRepository)
    {
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('admin_households', [$this, 'adminHouseholds']),
        ];
    }

    public function adminHouseholds(): array
    {
        return $this->householdRepository->findBy([], ['name' => 'ASC']);
    }
}
