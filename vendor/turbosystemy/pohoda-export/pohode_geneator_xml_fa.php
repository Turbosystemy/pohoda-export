<?php
declare(strict_types=1);




include 'vendor/autoload.php';

spl_autoload_register(function ($class) {
    echo "Načítá se třída: $class\n";
});


$pohoda = new Pohoda\Export('12345678');


try {
    // cislo faktury
    $invoice = new Pohoda\Invoice();

    // cena faktury s DPH (po staru) - volitelně
    $invoice->setText('faktura za prace ...');
    $price = 1000;

    //nizsi sazba dph
    $invoice->setPriceLow($price); //cena bez dph ve snizene sazbe
    $invoice->setPriceLowVAT($price * 0.15); //samotna dan

    //nebo vyssi sazba dph
    $invoice->setPriceHigh($price); //cena bez dph ve zvysene sazbe
//    $invoice->setPriceHighVAT($price * 0.21); //samotna dan
    $invoice->setPriceHighSum($price * 1.21); //cena s dph ve zvysene sazbe

    $invoice->setWithVat(true); //viz inv:classificationVAT - true nastavi cleneni dph na inland - tuzemske plneni, jinak da nonSubsume - nezahrnovat do DPH

    $invoice->setActivity('eshop'); //cinnost v pohode [volitelne, typ:ids]
    $invoice->setCentre('stredisko'); //stredisko v pohode [volitelne, typ:ids]
    $invoice->setContract('zak1'); //zakazka v pohode [volitelne, typ:ids]

    //nebo pridanim polozek do faktury (nove)
    $invoice->setText('Faktura za zboží');
    //polozky na fakture
    $item = new Pohoda\InvoiceItem();
    $item->setText("Název produktu");
    $item->setQuantity(1); //pocet
    $item->setCode("x230"); //katalogove cislo
    $item->setUnit("ks"); //jednotka
    $item->setNote("červená"); //poznamka
    $item->setStockItem(230); //ID produktu v Pohode
    //nastaveni ceny je volitelne, Pohoda si umi vytahnout cenu ze sve databaze pokud je nastaven stockItem
    $item->setUnitPrice(1000); //cena
    $item->setRateVAT($item::VAT_HIGH); //21%
    $item->setPayVAT(false); //cena bez dph

    $invoice->addItem($item);

    // variabilni cislo
    $invoice->setVariableNumber('12345678');
    // datum vytvoreni faktury
    $invoice->setDateCreated('2014-01-24');
    // datum zdanitelneho plneni
    $invoice->setDateTax('2014-02-01');
    // datum splatnosti
    $invoice->setDateDue('2014-02-04');
    //datum vytvoreni objednavky
    $invoice->setDateOrder('2014-01-24');

    //cislo objednavky v eshopu
    $invoice->setNumberOrder(254);

    // nastaveni identity dodavatele
    $invoice->setProviderIdentity([
        "company" => "Firma s.r.o.",
        "city" => "Praha",
        "street" => "Nejaka ulice",
        "number" => "80/3",
        "zip" => "160 00",
        "ico" => "034234",
        "dic" => "CZ034234"
    ]);

    // nastaveni identity prijemce
    $customer = [
        "company" => "Firma s.r.o.",
        "city" => "Praha",
        "street" => "Nejaka ulice",
        "number" => "80/3",
        "zip" => "160 00",
        "ico" => "034234",
        "dic" => "CZ034234",
        "icDph" => "SK....", //volitelne, v pripade slovenskeho platce dph
        "country" => "CZ", //volitelne, z ciselniku pohody
    ];
    $customerAddress =
        new Pohoda\Export\Address(
            new Pohoda\Object\Identity(
                'a01', //identifikator zakaznika [pokud neni zadan, nebude propojen s adresarem]
                new Pohoda\Object\Address($customer), //adresa zakaznika
                new Pohoda\Object\Address(["street" => "Pod Mostem"]) //pripadne dodaci adresa
            )
        );
    $invoice->setCustomerAddress($customerAddress);

    // nebo jednoduseji identitu nechat vytvorit
    //$customerAddress = $invoice->createCustomerAddress($customer, 0, ["street" => "Pod Mostem"]);

    if ($invoice->isValid()) {
        // pokud je faktura validni, pridame ji do exportu
        $pohoda->addInvoice($invoice);
        //pokud se ma importovat do adresare
        //$pohoda->addAddress($customerAddress);
    }
    else {
        var_dump($invoice->getErrors());
    }

// ulozeni do souboru
    $errorsNo = 0; // pokud si pocitate chyby, projevi se to v nazvu souboru
    $pohoda->setExportFolder(__DIR__ . "/export/pohoda"); //mozno nastavit slozku, do ktere bude proveden export
    $pohoda->exportToFile(time(), 'popis', date("Y-m-d_H-i-s"), $errorsNo);

} catch (Pohoda\InvoiceException $e) {
    echo $e->getMessage();
} catch (\InvalidArgumentException $e) {
    echo $e->getMessage();
}
