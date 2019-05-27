<?php

namespace DpdConnect\Sdk\Objects\ShipmentOrder\Shipment;

use DateTimeInterface;
use DpdConnect\Sdk\Objects\BaseObject;
use DpdConnect\Sdk\Objects\Customs\CustomsLine;
use JsonSerializable;

class Customs extends BaseObject implements JsonSerializable
{
    /**
     * @var string
     */
    protected $terms;

    /**
     * @var string
     */
    protected $reasonForExport;

    /**
     * @var float
     */
    protected $totalAmount;

    /**
     * @var string
     */
    protected $totalCurrency;

    /**
     * @var array
     */
    protected $documentTypes;

    /**
     * @var string
     */
    protected $invoiceNumber;

    /**
     * @var DateTimeInterface
     */
    protected $invoiceDate;

    /**
     * @var array
     */
    protected $movementReferences;

    /**
     * @var CustomsLine[[
     */
    protected $customsLines;

    /**
     * @var Consignee
     */
    protected $consignee;

    /**
     * @var Consignor
     */
    protected $consignor;
}
