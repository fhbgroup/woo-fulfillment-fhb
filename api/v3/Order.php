<?php

namespace Kika\Api\V3;


/**
 * Represents an order both ways: OrderApi::read() returns one wrapping
 * a decoded API response (use the getters), and you can build one from
 * scratch with the setters and hand it to OrderApi::create() to insert
 * a new order into the WMS.
 */
class Order extends View
{

	// --- fields settable when creating an order ---


	public function setId($id)
	{
		return $this->set('id', $id);
	}


	public function setVariableSymbol($variableSymbol)
	{
		return $this->set('variable_symbol', $variableSymbol);
	}


	public function setParcelService($parcelService)
	{
		return $this->set('parcel_service', $parcelService);
	}


	public function setDeliveryPoint($deliveryPoint)
	{
		return $this->set('delivery_point', $deliveryPoint);
	}


	public function setDeliveryPointParameters(array $parameters)
	{
		return $this->set('delivery_point_parameters', $parameters);
	}


	public function setCod($cod)
	{
		return $this->set('cod', $cod);
	}


	public function setValue($value)
	{
		return $this->set('value', $value);
	}


	public function setSender($sender)
	{
		return $this->set('sender', $sender);
	}


	public function setValidate($validate)
	{
		return $this->set('validate', $validate);
	}


	/**
	 * @param array $address {name, street, city, zip, province, country}
	 * @param array $contact {email, phone}
	 */
	public function setRecipient(array $address, array $contact)
	{
		return $this->set('recipient', ['address' => $address, 'contact' => $contact]);
	}


	/** @param array $items list of ['id' => ..., 'quantity' => ...] */
	public function setItems(array $items)
	{
		return $this->set('items', $items);
	}


	public function addItem($id, $quantity)
	{
		$items = $this->get('items', []);
		$items[] = ['id' => $id, 'quantity' => $quantity];
		return $this->set('items', $items);
	}


	/** @param array $notification any of confirmed/sent/delivered/returned/ticket_created/ticket_updated/ticket_closed URLs */
	public function setNotification(array $notification)
	{
		return $this->set('notification', $notification);
	}


	public function setInvoice($invoice)
	{
		return $this->set('invoice', $invoice);
	}


	public function setInvoiceNumber($invoiceNumber)
	{
		return $this->set('invoice_number', $invoiceNumber);
	}


	public function setInvoiceDate($invoiceDate)
	{
		return $this->set('invoice_date', $invoiceDate);
	}


	public function setNote($note)
	{
		return $this->set('note', $note);
	}


	public function setPrintDeliveryNote($printDeliveryNote)
	{
		return $this->set('print_delivery_note', $printDeliveryNote);
	}


	public function setPrintSscc($printSscc)
	{
		return $this->set('print_sscc', $printSscc);
	}


	/** @param array $invoiceDetail required for non-EU countries, see API docs for InvoiceDetail shape */
	public function setInvoiceDetail(array $invoiceDetail)
	{
		return $this->set('invoice_detail', $invoiceDetail);
	}


	// --- fields read back from the API ---


	public function getId()
	{
		return $this->get('id');
	}


	public function getStatus()
	{
		return $this->get('status');
	}


	public function getCreatedAt()
	{
		return $this->get('created_at');
	}


	public function getShippedAt()
	{
		return $this->get('shipped_at');
	}


	public function getDeliveredAt()
	{
		return $this->get('delivered_at');
	}


	public function getReturnedAt()
	{
		return $this->get('returned_at');
	}


	public function getReturnType()
	{
		return $this->get('return_type');
	}


	public function getParcelService()
	{
		return $this->get('parcel_service');
	}


	public function getSender()
	{
		return $this->get('sender');
	}


	public function getTrackingNumbers()
	{
		return $this->get('tracking_numbers', []);
	}


	public function getTrackingLinks()
	{
		return $this->get('tracking_links', []);
	}


	/** @return OrderItem[] */
	public function getItems()
	{
		return array_map(function($item) {
			return new OrderItem($item);
		}, $this->get('items', []));
	}


	/** @return PackingPackage[] */
	public function getPackingPackages()
	{
		return array_map(function($packingPackage) {
			return new PackingPackage($packingPackage);
		}, $this->get('packing_packages', []));
	}


	/** @return Package[] */
	public function getPackages()
	{
		return array_map(function($package) {
			return new Package($package);
		}, $this->get('packages', []));
	}


	/** @return TrackingEvent[] */
	public function getTrackingEvents()
	{
		return array_map(function($event) {
			return new TrackingEvent($event);
		}, $this->get('tracking_events', []));
	}


	/** @return PackageReturn[] */
	public function getPackageReturns()
	{
		return array_map(function($packageReturn) {
			return new PackageReturn($packageReturn);
		}, $this->get('package_returns', []));
	}


	public function getProofOfDelivery()
	{
		$data = $this->get('proof_of_delivery');
		return $data ? new ProofOfDelivery($data) : null;
	}

}


class OrderItem extends View
{

	public function getId()
	{
		return $this->get('id');
	}


	public function getQuantity()
	{
		return $this->get('quantity');
	}


	public function getSerialNumbers()
	{
		return $this->get('serial_numbers', []);
	}

}


class PackingPackage extends View
{

	public function getNumber()
	{
		return $this->get('number');
	}


	public function getSscc()
	{
		return $this->get('sscc');
	}

}


class Package extends View
{

	public function getTrackingNumber()
	{
		return $this->get('tracking_number');
	}


	public function getWeight()
	{
		return $this->get('weight');
	}


	public function getPackageDimension()
	{
		$data = $this->get('package_dimension');
		return $data ? new PackageDimension($data) : null;
	}

}


class PackageDimension extends View
{

	public function getLength()
	{
		return $this->get('length');
	}


	public function getWidth()
	{
		return $this->get('width');
	}


	public function getHeight()
	{
		return $this->get('height');
	}


	public function getVolumetricWeight()
	{
		return $this->get('volumetric_weight');
	}

}


class TrackingEvent extends View
{

	public function getDate()
	{
		return $this->get('date');
	}


	public function getMessage()
	{
		return $this->get('message');
	}


	public function getEventCode()
	{
		return $this->get('event_code');
	}


	public function getCarrierEventCode()
	{
		return $this->get('carrier_event_code');
	}

}


class PackageReturn extends View
{

	public function getTracking()
	{
		return $this->get('tracking');
	}


	public function getReturnTracking()
	{
		return $this->get('return_tracking');
	}

}


class ProofOfDelivery extends View
{

	public function getRecipient()
	{
		return $this->get('recipient');
	}


	public function getDate()
	{
		return $this->get('date');
	}

}
