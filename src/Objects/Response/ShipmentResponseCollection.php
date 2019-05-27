<?php

namespace DpdConnect\Sdk\Objects\Response;

use DpdConnect\Sdk\Api\ShipmentLabelInterface;
use DpdConnect\Sdk\Objects\Response\CreateShipment\LabelInterface;
use DpdConnect\Sdk\Objects\Response\Generic\ResponseStatus;
use DpdConnect\Sdk\Objects\Response\Generic\ResponseStatusInterface;

class ShipmentResponseCollection extends \ArrayIterator implements ShipmentResponseInterface
{
    const MSG_STATUS_CREATED           = 'Shipping labels were created successfully.';
    const MSG_STATUS_PARTIALLY_CREATED = 'Some shipping labels could not be created: %s';
    const MSG_STATUS_NOT_CREATED       = 'Shipping labels could not be created: %s';

    /**
     * @var ResponseStatusInterface
     */
    private $status;

    /**
     * @param LabelInterface[] $labels
     * @param string[]         $invalidOrders
     *
     * @return string[]
     */
    private static function getStatusMessages(array $labels, array $invalidOrders = [])
    {
        $messages = [];

//        foreach ($labels as $label) {
//            $messages[] = sprintf(
//                '%s: %s | %s',
//                $label->getSequenceNumber(),
//                $label->getStatus()->getText(),
//                $label->getStatus()->getMessage()
//            );
//        }
////
//        foreach ($invalidOrders as $sequenceNumber => $errorMessage) {
//            $messages[] = sprintf(
//                '%s: %s',
//                $sequenceNumber,
//                $errorMessage
//            );
//        }

        return $messages;
    }

    /**
     * Infer overall operation status from single items' status.
     *
     * - All labels created: success
     * - No labels created: not created
     * - Some labels created: partially created
     *
     * @param LabelInterface[] $labels
     * @param string[]         $invalidOrders
     *
     * @return ResponseStatus
     */
    private static function getResponseStatus(array $labels, array $invalidOrders = [])
    {
        if (count($labels) === 0) {
            $messages = self::getStatusMessages($labels, $invalidOrders);
            $responseStatus = new ResponseStatus(
                ResponseStatusInterface::STATUS_FAILURE,
                'Error',
                sprintf(self::MSG_STATUS_NOT_CREATED, implode("\n", $messages))
            );

            return $responseStatus;
        }

        $createdLabels = array_filter($labels, function (ShipmentLabelInterface $label) {
            return ($label->getStatus() === ResponseStatusInterface::STATUS_SUCCESS);
        });
        $rejectedLabels = array_filter($labels, function (ShipmentLabelInterface $label) {
            return ($label->getStatus() === ResponseStatusInterface::STATUS_FAILURE);
        });

        if (empty($rejectedLabels) && empty($invalidOrders)) {
            $responseStatus = new ResponseStatus(
                ResponseStatusInterface::STATUS_SUCCESS,
                'Info',
                self::MSG_STATUS_CREATED
            );
        } elseif (empty($createdLabels)) {
            $messages = self::getStatusMessages($rejectedLabels, $invalidOrders);
            $responseStatus = new ResponseStatus(
                ResponseStatusInterface::STATUS_FAILURE,
                'Error',
                sprintf(self::MSG_STATUS_NOT_CREATED, implode("\n", $messages))
            );
        } else {
            $messages = self::getStatusMessages($rejectedLabels, $invalidOrders);
            $responseStatus = new ResponseStatus(
                ResponseStatusInterface::STATUS_PARTIAL_SUCCESS,
                'Warning',
                sprintf(self::MSG_STATUS_PARTIALLY_CREATED, implode("\n", $messages))
            );
        }

        return $responseStatus;
    }

    /**
     * @return ResponseStatusInterface
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param ResponseStatusInterface $status
     *
     * @return $this
     */
    public function setStatus(ResponseStatusInterface $status)
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return LabelInterface[]
     */
    public function getItems()
    {
        return $this->getArrayCopy();
    }

    /**
     * @param string $sequenceNumber
     *
     * @return LabelInterface
     */
    public function getItem($sequenceNumber)
    {
        return $this->offsetGet($sequenceNumber);
    }

    /**
     * @param LabelInterface[] $labels
     * @param string[]         $invalidRequests
     *
     * @return ShipmentResponseCollection
     */
    public static function fromResponse(array $labels, array $invalidRequests)
    {
        $collection = new self($labels);

        $responseStatus = self::getResponseStatus($labels, $invalidRequests);
        $collection->setStatus($responseStatus);

        return $collection;
    }

    /**
     * @param ApiException $exception
     * @param string[]            $invalidRequests
     *
     * @return ShipmentResponseCollection
     */
    public static function fromError(\Exception $exception, array $invalidRequests)
    {
        $collection = new self([]);

        $messages = self::getStatusMessages([], $invalidRequests);
        if ($exception->getPrevious()) {
            $messages[] = $exception->getPrevious()->getMessage();
        } else {
            $messages[] = $exception->getMessage();
        }

        $responseStatus = new ResponseStatus(
            ResponseStatusInterface::STATUS_FAILURE,
            'Error',
            sprintf(self::MSG_STATUS_NOT_CREATED, implode("\n", $messages))
        );
        $collection->setStatus($responseStatus);

        return $collection;
    }
}
