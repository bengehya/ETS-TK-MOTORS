<?php

namespace App\Enums;

enum Permission: string
{
    case ManageEmployees = 'manage_employees';
    case InviteBoss = 'invite_boss';
    case ManageProducts = 'manage_products';
    case UpdatePrices = 'update_prices';
    case ValidateStockReceipts = 'validate_stock_receipts';
    case ManageSales = 'manage_sales';
    case CreateSales = 'create_sales';
    case CancelSales = 'cancel_sales';
    case ManageExpenses = 'manage_expenses';
    case ViewReports = 'view_reports';
    case ViewAudit = 'view_audit';
    case SearchProducts = 'search_products';
    case ScanProducts = 'scan_products';
    case RecordStockReceipts = 'record_stock_receipts';
    case CreateCustomerRequests = 'create_customer_requests';

    public function label(): string
    {
        return match ($this) {
            self::ManageEmployees => 'Gérer les employés',
            self::InviteBoss => 'Inviter un patron',
            self::ManageProducts => 'Gérer les articles',
            self::UpdatePrices => 'Modifier les prix',
            self::ValidateStockReceipts => 'Valider les arrivages',
            self::ManageSales => 'Gérer les ventes',
            self::CreateSales => 'Enregistrer une vente',
            self::CancelSales => 'Annuler une vente',
            self::ManageExpenses => 'Gérer les dépenses',
            self::ViewReports => 'Consulter les rapports',
            self::ViewAudit => 'Consulter l’audit',
            self::SearchProducts => 'Rechercher des articles',
            self::ScanProducts => 'Scanner des articles',
            self::RecordStockReceipts => 'Enregistrer un arrivage',
            self::CreateCustomerRequests => 'Enregistrer une demande client',
        };
    }
}
