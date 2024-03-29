<?php

namespace APP\Models;

use APP\Helpers\PublicHelper\PublicHelper;
use ArrayIterator;
use Exception;
use ReflectionException;

class TransactionsSalesModel extends AbstractModel
{
    use TraitTransactionsModel;
    use PublicHelper;
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
    public static function getInfoSalesInvoice(NULL | array $filters = null): false|\ArrayIterator
    {
        $sql = "
            SELECT 
                sales_invoices.*, sales_invoices_receipts.*, clients.* 
            FROM 
                sales_invoices
            INNER JOIN 
                    sales_invoices_receipts
            ON 
                sales_invoices_receipts.InvoiceId = sales_invoices.InvoiceId 
            INNER JOIN
                    clients
            ON 
                sales_invoices.ClientId = clients.ClientId
        ";

        if ($filters != null) {
            (new TransactionsSalesModel)->addSearchTerm($sql, $filters, (new TransactionsSalesModel)->setSchema([
                (new SalesInvoicesModel()),
                (new SalesInvoicesReceiptsModel()),
                (new ClientModel())
            ]));
        }

        
        return (new TransactionsSalesModel)->get($sql);
    }

    public static function getInvoice($id)
    {
        $sql = "
            SELECT 
               DISTINCT I.*, R.*, C.* 
            FROM 
                sales_invoices AS I
            INNER JOIN 
                    sales_invoices_receipts AS R
            ON 
                R.InvoiceId = I.InvoiceId 
            JOIN 
                    clients AS C
            ON
                I.ClientId = C.ClientId
            WHERE I." . static::$primaryKey . " = ". $id . "
        ";

        return (new TransactionsSalesModel)->getRow($sql);
        
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
                    sales_invoices_details AS SID 
                ON 
                    P.ProductId = SID.ProductId 
                WHERE 
                    SID.InvoiceId = {$id}";
        return (new TransactionsSalesModel())->get($sql);
    }

    /**
     * to get last invoices sales
     * @param int $limit number of last invoices default return last 5 invoice sales
     * @param null $filters $_POST if you to filter box activate
     * @return false|ArrayIterator
     * @throws ReflectionException
     * @version 1.2
     * @author Feras Barahemh
     */
    public static function lastInvoice(int $limit=5, $filters=null): false|ArrayIterator
    {
        $sql = "
            SELECT 
               DISTINCT sales_invoices.*, sales_invoices_receipts.*, clients.* 
            FROM 
                sales_invoices
            INNER JOIN 
                    sales_invoices_receipts
            ON 
                sales_invoices_receipts.InvoiceId = sales_invoices.InvoiceId 
            JOIN 
                    clients 
            ON
                sales_invoices.ClientId = clients.ClientId
            
        ";
        if ($filters != null) {

            (new TransactionsPurchasesModel)->addSearchTerm($sql, $filters, (new TransactionsPurchasesModel)->setSchema([
                (new SalesInvoicesModel()),
                (new SalesInvoicesReceiptsModel()),
                (new ClientModel())
            ]));
        }

        $sql .= " LIMIT {$limit}";
        
        return (new TransactionsSalesModel())->get($sql);
    }

    /**
     * get sum revenue today
     * @return mixed
     */
    public static function revenueToday(): mixed
    {
        $sql = "
            SELECT 
                SUM(PaymentAmount) AS revenue
            FROM 
                sales_invoices_receipts
            WHERE
                DATE(created) = (SELECT CURRENT_DATE())
        ";
        return (new TransactionsSalesModel())->getRow($sql)->revenue;
    }

    /**
     * to get Financial Receivables Today
     * @return mixed
     */
    public static function financialReceivablesToday(): mixed
    {
        $sql = "
            SELECT 
                SUM(PaymentLiteral) AS revenue
            FROM 
                sales_invoices_receipts
            WHERE
                DATE(created) = (SELECT CURRENT_DATE())
        ";
        return (new TransactionsSalesModel())->getRow($sql)->revenue;
    }

}