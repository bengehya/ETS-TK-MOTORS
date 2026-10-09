<?php

namespace Tests\Feature\Interface;

use Tests\TestCase;

class InternalCopyTest extends TestCase
{
    public function test_internal_explanations_are_absent_from_the_interface(): void
    {
        $forbidden = [
            'Le dépôt n’est jamais vendable en boutique',
            'L’enregistrement ne change pas le stock',
            'Le patron principal peut inviter un employé',
            'L’envoi par e-mail n’est pas disponible',
            'L’envoi d’e-mail n’est pas disponible',
            'L’aperçu ci-dessous est indicatif',
            'Elle n’achète rien et ne déplace aucun stock',
            'Un change transfère de la valeur',
            'Ce transfert n’est pas un bénéfice',
            'Identifiant unique interne',
            'Donnée interne, utilisée plus tard',
            'L’enregistrement ne débite pas la caisse',
            'Cette suggestion n’est pas automatique',
            'ne sont pas additionnés',
            'Ces déclarations ne modifient pas les soldes',
            'Une note ou un comptage ne change pas',
            'Le stock dépôt n’est jamais vendable',
            'Ce formulaire n’annule pas une vente',
            'La validation débite la caisse',
            'Rien n’est déduit de votre adresse',
            'Le stock n’augmente qu’après validation',
            'La caisse enregistrera le total de la vente',
        ];

        $contents = '';

        foreach ([resource_path('js'), resource_path('views'), app_path('Http')] as $directory) {
            $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($directory));

            foreach ($iterator as $file) {
                if (! $file->isFile()) {
                    continue;
                }

                $contents .= file_get_contents($file->getPathname());
            }
        }

        foreach ($forbidden as $sentence) {
            $this->assertStringNotContainsString($sentence, $contents, $sentence);
        }
    }

    public function test_the_amount_received_field_is_not_a_number_input(): void
    {
        $source = file_get_contents(resource_path('js/Pages/Sales/Create.vue'));

        $this->assertIsString($source);
        $this->assertStringContainsString('inputmode="decimal"', $source);
        $this->assertStringContainsString('id="amount_received"', $source);
        $this->assertStringNotContainsString('amountReceived.value.trim()', $source);
        $this->assertStringNotContainsString('type="number" min="0" step="0.01"', $source);
        $this->assertStringContainsString('Monnaie à remettre', file_get_contents(resource_path('js/utils/moneyInput.ts')));
    }
}
