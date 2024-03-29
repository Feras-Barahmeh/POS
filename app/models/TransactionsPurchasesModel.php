<?php

namespace APP\Models;

use ArrayIterator;
use Exception;
use ReflectionException;

class TransactionsPurchasesModel extends AbstractModel
{
    use TraitTransactionsModel;
    private SalesInvoicesModel $salesInvoicesModel;
    private SalesInvoicesDetailsModel $salesInvoicesDetailsModel;
    private SalesInvoicesReceiptsModel $salesInvoicesReceiptsModel;
    protected static string $primaryKey = "InvoiceId";

    public function __constructor()
    {
        $this->salesInvoicesModel           = new SalesInvoicesModel();
        $this->salesInvoicesDetailsModel    = new SalesInvoicesDetailsModel();
        $this->salesInvoicesReceiptsModel   = new SalesInvoicesReceiptsModel();
    }

    /**
     * To get all information you need to show invoice in transaction page
     * @param array|NULL $filters
     * @return false|ArrayIterator
     * @throws Exception
     */
    public static function getInfoPurchasesInvoice(NULL | array $filters = null): false|\ArrayIterator
    {
        $sql = "
            SELECT 
                purchases_invoices.*, purchases_invoices_receipts.*, suppliers.* 
            FROM 
                purchases_invoices 
            INNER JOIN 
                    purchases_invoices_receipts
            ON 
                purchases_invoices_receipts.InvoiceId = purchases_invoices.InvoiceId 
            JOIN 
                    suppliers
            ON 
                purchases_invoices.SupplierId = suppliers.SupplierId
        ";

        if ($filters != null) {
            (new TransactionsPurchasesModel)->addSearchTerm($sql, $filters, (new TransactionsPurchasesModel)->setSchema([
                (new PurchasesInvoicesModel()),
                (new PurchasesInvoicesReceiptsModel()),
                (new SupplierModel())
            ]));
        }
        
        return (new TransactionsPurchasesModel())->get($sql);
    }

    public static function getInvoice(int $id)
    {
        $sql = "
            SELECT 
                I.*, R.*, C.* 
            FROM 
                purchases_invoices AS I 
            INNER JOIN 
                    purchases_invoices_receipts AS R 
            ON 
                R.InvoiceId = I.InvoiceId 
            JOIN 
                    suppliers as C 
            ON 
                I.SupplierId = C.SupplierId
            WHERE I.". static::$primaryKey ." = {$id}
        ";

        return (new TransactionsPurchasesModel())->getRow($sql);
    }
    /**
     *
     * method to get all products in this invoice
     * @param int $id The invoice number containing the products
     * @return false|ArrayIterator false if not products else return products
     */
    public static function getProductsInvoice(int $id): false|ArrayIterator
    {

        $sql = "SELECT 
                    * 
                FROM 
                    products AS P 
                INNER JOIN 
                    purchases_invoices_details AS SID 
                ON 
                    P.ProductId = SID.ProductId 
                WHERE 
                    SID.InvoiceId = {$id}";
        return (new TransactionsPurchasesModel())->get($sql);
    }

    /**
     * to get last invoices purchases
     * @param int $limit number of last invoices default return last 5 invoice purchases
     * @param null $filters $_POST if you to filter box activate
     * @return false|ArrayIterator
     * @throws ReflectionException
     * @version 1.2
     * @author Feras Barahemh
     */
    public static function lastInvoices(int $limit=5, $filters=null): false|ArrayIterator
    {
        $sql = "
            SELECT 
                purchases_invoices.*, purchases_invoices_receipts.*, suppliers.* 
            FROM 
                purchases_invoices
            INNER JOIN 
                    purchases_invoices_receipts
            ON 
                purchases_invoices_receipts.InvoiceId = purchases_invoices.InvoiceId 
            JOIN 
                    suppliers
            ON 
                purchases_invoices.SupplierId = suppliers.SupplierId
            
        ";
        if ($filters != null) {
            (new TransactionsPurchasesModel)->addSearchTerm($sql, $filters, (new TransactionsPurchasesModel)->setSchema([
                (new PurchasesInvoicesModel()),
                (new PurchasesInvoicesReceiptsModel()),
                (new SupplierModel())
            ]));
        }
        $sql .= " LIMIT {$limit}";
        return (new TransactionsPurchasesModel())->get($sql);
    }
    public static function revenueToday()
    {
        $sql = "
            SELECT 
                SUM(PaymentAmount) AS revenue
            FROM 
                purchases_invoices_receipts
            WHERE
                DATE(created) = (SELECT CURRENT_DATE())
        ";
        return (new TransactionsSalesModel())->getRow($sql)->revenue;
    }
    public static function financialReceivablesToday()
    {
        $sql = "
            SELECT 
                SUM(PaymentLiteral) AS revenue
            FROM 
                purchases_invoices_receipts
            WHERE
                DATE(created) = (SELECT CURRENT_DATE())
        ";
        return (new TransactionsSalesModel())->getRow($sql)->revenue;
    }
}