<?php

namespace GoodGeek\FakeData\Console\Command;

use Magento\Framework\App\State;
use Magento\Framework\Console\Cli;
use Magento\Framework\Exception\LocalizedException;
use Magento\Customer\Model\ResourceModel\Customer\CollectionFactory as CustomerCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\CollectionFactory as OrderCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Invoice\CollectionFactory as InvoiceCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Shipment\CollectionFactory as ShipmentCollectionFactory;
use Magento\Sales\Model\ResourceModel\Order\Creditmemo\CollectionFactory as CreditmemoCollectionFactory;
use Magento\Quote\Model\ResourceModel\Quote\Address as QuoteAddressResource;
use Magento\Framework\App\ResourceConnection;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Magento\Indexer\Model\Processor;
use Faker\Factory as FakerFactory;


class AnonymizeData extends Command
{
    protected $state;
    protected $customerCollectionFactory;
    protected $orderCollectionFactory;
    protected $invoiceCollectionFactory;
    protected $shipmentCollectionFactory;
    protected $creditmemoCollectionFactory;
    protected $quoteAddressResource;
    protected $resourceConnection;
    protected $indexerProcessor;

    public function __construct(
        State $state,
        CustomerCollectionFactory $customerCollectionFactory,
        OrderCollectionFactory $orderCollectionFactory,
        InvoiceCollectionFactory $invoiceCollectionFactory,
        ShipmentCollectionFactory $shipmentCollectionFactory,
        CreditmemoCollectionFactory $creditmemoCollectionFactory,
        QuoteAddressResource $quoteAddressResource,
        ResourceConnection $resourceConnection,
        Processor $indexerProcessor
    ) {
        $this->state = $state;
        $this->customerCollectionFactory = $customerCollectionFactory;
        $this->orderCollectionFactory = $orderCollectionFactory;
        $this->invoiceCollectionFactory = $invoiceCollectionFactory;
        $this->shipmentCollectionFactory = $shipmentCollectionFactory;
        $this->creditmemoCollectionFactory = $creditmemoCollectionFactory;
        $this->quoteAddressResource = $quoteAddressResource;
        $this->resourceConnection = $resourceConnection;
        $this->indexerProcessor = $indexerProcessor;
        parent::__construct();
    }

    protected function configure()
    {
        $this->setName('goodgeek:fakedata:anonymize')
            ->setDescription('Anonymize customer, guest, order, and invoice data with log output');
        parent::configure();
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $this->state->setAreaCode('adminhtml');
        $faker = FakerFactory::create();
        $connection = $this->resourceConnection->getConnection();

        try {
            // Анонимизация данных клиентов
            $output->writeln("<info>Анонимизация данных клиентов...</info>");
            $customerCollection = $this->customerCollectionFactory->create();

            foreach ($customerCollection as $customer) {
                $firstName = $faker->firstName;
                $middleName = $faker->firstName;
                $lastName = $faker->lastName;
                $email = $faker->unique()->safeEmail;
                $company = $faker->company;
                $city = $faker->city;
                $postcode = $faker->postcode;
                $telephone = $faker->phoneNumber;
                $street = $faker->streetAddress;
                $vatId = $faker->regexify('[A-Z]{2}[0-9]{9}');
                $countryId = $faker->countryCode;
                $region = $faker->state;
                $regionId = rand(1, 999);  // Для примера произвольный регион ID
                $fax = $faker->phoneNumber;

                // Анонимизация профиля клиента
                if ($customer->getFirstname()) {
                    $customer->setFirstname($firstName);
                }
                if ($customer->getMiddlename()) {
                    $customer->setMiddlename($middleName);
                }
                if ($customer->getLastname()) {
                    $customer->setLastname($lastName);
                }
                if ($customer->getEmail()) {
                    $customer->setEmail($email);
                }

                // Анонимизация VAT/TAX Number
                if ($customer->getTaxvat()) {
                    $customer->setTaxvat($vatId);
                }

                // Сохраняем изменения профиля клиента
                $customer->save();
                $output->writeln("Клиент ID: {$customer->getId()} изменен.");

                // Обновление данных в таблице quote для клиента
                $connection->update(
                    'quote',
                    [
                        'customer_firstname' => $firstName,
                        'customer_middlename' => $middleName,
                        'customer_lastname' => $lastName,
                        'customer_email' => $email
                    ],
                    ['customer_id = ?' => $customer->getId()]
                );
                $output->writeln("Данные в quote для клиента ID: {$customer->getId()} обновлены.");

                // Анонимизация адресов клиента
                $addresses = $customer->getAddresses();
                foreach ($addresses as $address) {
                    if ($address->getFirstname()) {
                        $address->setFirstname($firstName);
                    }
                    if ($address->getMiddlename()) {
                        $address->setMiddlename($middleName);
                    }
                    if ($address->getLastname()) {
                        $address->setLastname($lastName);
                    }

                    if ($address->getCity()) {
                        $address->setCity($city);
                    }
                    if ($address->getPostcode()) {
                        $address->setPostcode($postcode);
                    }
                    if ($address->getTelephone()) {
                        $address->setTelephone($telephone);
                    }
                    if ($address->getStreet()) {
                        $address->setStreet($street);
                    }
                    if ($address->getCompany()) {
                        $address->setCompany($company);
                    }
                    if ($address->getVatId()) {
                        $address->setVatId($vatId);
                    }
                    if ($address->getCountryId()) {
                        $address->setCountryId($countryId);
                    }
                    if ($address->getRegion()) {
                        $address->setRegion($region);
                    }
                    if ($address->getRegionId()) {
                        $address->setRegionId($regionId);
                    }
                    if ($address->getFax()) {
                        $address->setFax($fax);
                    }

                    // Сохраняем изменения адреса
                    $address->save();
                    $output->writeln("Адрес ID: {$address->getId()} изменен.");
                }

                // Анонимизация данных в таблице quote_address
                $connection->update(
                    'quote_address',
                    [
                        'firstname' => $firstName,
                        'middlename' => $middleName,
                        'lastname' => $lastName,
                        'email' => $email,
                        'city' => $city,
                        'postcode' => $postcode,
                        'telephone' => $telephone,
                        'street' => $street,
                        'company' => $company,
                        'country_id' => $countryId,
                        'region' => $region,
                        'region_id' => $regionId,
                        'fax' => $fax
                    ],
                    ['customer_id = ?' => $customer->getId()]
                );
                $output->writeln("Данные в quote_address для клиента ID: {$customer->getId()} обновлены.");

                // Анонимизация заказов клиента
                $orderCollection = $this->orderCollectionFactory->create()
                    ->addFieldToFilter('customer_id', $customer->getId());

                foreach ($orderCollection as $order) {
                    if ($order->getId()) {
                        if ($order->getCustomerFirstname()) {
                            $order->setCustomerFirstname($firstName);
                        }
                        if ($order->getCustomerMiddlename()) {
                            $order->setCustomerMiddlename($middleName);
                        }
                        if ($order->getCustomerLastname()) {
                            $order->setCustomerLastname($lastName);
                        }
                        if ($order->getCustomerEmail()) {
                            $order->setCustomerEmail($email);
                        }

                        // Анонимизация Billing и Shipping Address
                        $billingAddress = $order->getBillingAddress();
                        $shippingAddress = $order->getShippingAddress();

                        if ($billingAddress && $billingAddress->getId()) {
                            if ($billingAddress->getFirstname()) {
                                $billingAddress->setFirstname($firstName);
                            }
                            if ($billingAddress->getMiddlename()) {
                                $billingAddress->setMiddlename($middleName);
                            }
                            if ($billingAddress->getLastname()) {
                                $billingAddress->setLastname($lastName);
                            }

                            if ($billingAddress->getEmail()) {
                                $billingAddress->setEmail($email);
                            }

                            if ($billingAddress->getStreet()) {
                                $billingAddress->setStreet($street);
                            }
                            if ($billingAddress->getCity()) {
                                $billingAddress->setCity($city);
                            }
                            if ($billingAddress->getPostcode()) {
                                $billingAddress->setPostcode($postcode);
                            }
                            if ($billingAddress->getTelephone()) {
                                $billingAddress->setTelephone($telephone);
                            }
                            if ($billingAddress->getVatId()) {
                                $billingAddress->setVatId($vatId);
                            }
                            if ($billingAddress->getCompany()) {
                                $billingAddress->setCompany($company);
                            }
                            if ($billingAddress->getCountryId()) {
                                $billingAddress->setCountryId($countryId);
                            }
                            if ($billingAddress->getRegion()) {
                                $billingAddress->setRegion($region);
                            }
                            if ($billingAddress->getRegionId()) {
                                $billingAddress->setRegionId($regionId);
                            }
                            if ($billingAddress->getFax()) {
                                $billingAddress->setFax($fax);
                            }

                            $billingAddress->save();
                            $output->writeln("Billing адрес ID: {$billingAddress->getId()} изменен.");
                        }

                        if ($shippingAddress && $shippingAddress->getId()) {
                            if ($shippingAddress->getFirstname()) {
                                $shippingAddress->setFirstname($firstName);
                            }
                            if ($shippingAddress->getMiddlename()) {
                                $shippingAddress->setMiddlename($middleName);
                            }
                            if ($shippingAddress->getLastname()) {
                                $shippingAddress->setLastname($lastName);
                            }

                            if ($shippingAddress->getEmail()) {
                                $shippingAddress->setEmail($email);
                            }

                            if ($shippingAddress->getStreet()) {
                                $shippingAddress->setStreet($street);
                            }
                            if ($shippingAddress->getCity()) {
                                $shippingAddress->setCity($city);
                            }
                            if ($shippingAddress->getPostcode()) {
                                $shippingAddress->setPostcode($postcode);
                            }
                            if ($shippingAddress->getTelephone()) {
                                $shippingAddress->setTelephone($telephone);
                            }
                            if ($shippingAddress->getVatId()) {
                                $shippingAddress->setVatId($vatId);
                            }
                            if ($shippingAddress->getCompany()) {
                                $shippingAddress->setCompany($company);
                            }
                            if ($shippingAddress->getCountryId()) {
                                $shippingAddress->setCountryId($countryId);
                            }
                            if ($shippingAddress->getRegion()) {
                                $shippingAddress->setRegion($region);
                            }
                            if ($shippingAddress->getRegionId()) {
                                $shippingAddress->setRegionId($regionId);
                            }
                            if ($shippingAddress->getFax()) {
                                $shippingAddress->setFax($fax);
                            }

                            $shippingAddress->save();
                            $output->writeln("Shipping адрес ID: {$shippingAddress->getId()} изменен.");
                        }

                        // Формирование строки для billing_address и shipping_address
                        $billingAddressFormatted = "{$company} {$firstName} {$middleName} {$lastName}, {$street}, {$city}, {$region}, {$postcode}";
                        $shippingAddressFormatted = "{$company} {$firstName} {$middleName} {$lastName}, {$street}, {$city}, {$region}, {$postcode}";

                        // Обновление данных в таблице sales_shipment_grid
                        $connection->update(
                            'sales_shipment_grid',
                            [
                                'shipping_name' => "{$firstName} {$middleName} {$lastName}",
                                'billing_name' => "{$firstName} {$middleName} {$lastName}",
                                'customer_name' => "{$firstName} {$middleName} {$lastName}",
                                'customer_email' => $email,
                                'billing_address' => $billingAddressFormatted,
                                'shipping_address' => $shippingAddressFormatted
                            ],
                            ['order_id = ?' => $order->getId()]
                        );
                        $output->writeln("Доставка для заказа ID: {$order->getId()} обновлена.");

                        // Обновление данных в таблице sales_invoice_grid
                        $connection->update(
                            'sales_invoice_grid',
                            [
                                'customer_name' => "{$firstName} {$middleName} {$lastName}",
                                'billing_name' => "{$firstName} {$middleName} {$lastName}",
                                'customer_email' => $email,
                                'billing_address' => $billingAddressFormatted,
                                'shipping_address' => $shippingAddressFormatted
                            ],
                            ['order_id = ?' => $order->getId()]
                        );
                        $output->writeln("Инвойс для заказа ID: {$order->getId()} обновлен.");

                        // Обновление данных в таблице sales_creditmemo_grid
                        $connection->update(
                            'sales_creditmemo_grid',
                            [
                                'customer_name' => "{$firstName} {$middleName} {$lastName}",
                                'billing_name' => "{$firstName} {$middleName} {$lastName}",
                                'customer_email' => $email,
                                'billing_address' => $billingAddressFormatted,
                                'shipping_address' => $shippingAddressFormatted
                            ],
                            ['order_id = ?' => $order->getId()]
                        );
                        $output->writeln("Кредит-нота для заказа ID: {$order->getId()} обновлена.");


                        $order->save();
                        $output->writeln("Заказ ID: {$order->getId()} изменен.");
                    }
                }
            }

            // Анонимизация данных гостевых заказов
            $output->writeln('<info>Анонимизация данных гостевых заказов...</info>');
            $guestOrderCollection = $this->orderCollectionFactory->create()
                ->addFieldToFilter('customer_id', ['null' => true]);  // Заказы без привязки к клиентам

            foreach ($guestOrderCollection as $guestOrder) {
                $firstName = $faker->firstName;
                $middleName = $faker->firstName;
                $lastName = $faker->lastName;
                $email = $faker->unique()->safeEmail;
                $company = $faker->company;
                $city = $faker->city;
                $postcode = $faker->postcode;
                $telephone = $faker->phoneNumber;
                $street = $faker->streetAddress;
                $vatId = $faker->regexify('[A-Z]{2}[0-9]{9}');
                $countryId = $faker->countryCode;
                $region = $faker->state;
                $regionId = rand(1, 999);  // Для примера произвольный регион ID
                $fax = $faker->phoneNumber;

                $output->writeln("Гостевой заказ ID: {$guestOrder->getId()} до изменения.");

                // Анонимизация данных гостевого заказа
                if ($guestOrder->getCustomerFirstname()) {
                    $guestOrder->setCustomerFirstname($firstName);
                }
                if ($guestOrder->getCustomerMiddlename()) {
                    $guestOrder->setCustomerMiddlename($middleName);
                }
                if ($guestOrder->getCustomerLastname()) {
                    $guestOrder->setCustomerLastname($lastName);
                }
                if ($guestOrder->getCustomerEmail()) {
                    $guestOrder->setCustomerEmail($email);
                }

                // Анонимизация Billing и Shipping Address в гостевом заказе
                $billingAddress = $guestOrder->getBillingAddress();
                $shippingAddress = $guestOrder->getShippingAddress();

                if ($billingAddress && $billingAddress->getId()) {
                    if ($billingAddress->getFirstname()) {
                        $billingAddress->setFirstname($firstName);
                    }
                    if ($billingAddress->getMiddlename()) {
                        $billingAddress->setMiddlename($middleName);
                    }
                    if ($billingAddress->getLastname()) {
                        $billingAddress->setLastname($lastName);
                    }

                    if ($billingAddress->getEmail()) {
                        $billingAddress->setEmail($email);
                    }

                    if ($billingAddress->getStreet()) {
                        $billingAddress->setStreet($street);
                    }
                    if ($billingAddress->getCity()) {
                        $billingAddress->setCity($city);
                    }
                    if ($billingAddress->getPostcode()) {
                        $billingAddress->setPostcode($postcode);
                    }
                    if ($billingAddress->getTelephone()) {
                        $billingAddress->setTelephone($telephone);
                    }
                    if ($billingAddress->getVatId()) {
                        $billingAddress->setVatId($vatId);
                    }
                    if ($billingAddress->getCompany()) {
                        $billingAddress->setCompany($company);
                    }
                    if ($billingAddress->getCountryId()) {
                        $billingAddress->setCountryId($countryId);
                    }
                    if ($billingAddress->getRegion()) {
                        $billingAddress->setRegion($region);
                    }
                    if ($billingAddress->getRegionId()) {
                        $billingAddress->setRegionId($regionId);
                    }
                    if ($billingAddress->getFax()) {
                        $billingAddress->setFax($fax);
                    }

                    $billingAddress->save();
                    $output->writeln("Billing адрес для гостевого заказа ID: {$billingAddress->getId()} изменен.");
                }

                if ($shippingAddress && $shippingAddress->getId()) {
                    if ($shippingAddress->getFirstname()) {
                        $shippingAddress->setFirstname($firstName);
                    }
                    if ($shippingAddress->getMiddlename()) {
                        $shippingAddress->setMiddlename($middleName);
                    }
                    if ($shippingAddress->getLastname()) {
                        $shippingAddress->setLastname($lastName);
                    }
                    if ($shippingAddress->getEmail()) {
                        $shippingAddress->setEmail($email);
                    }

                    if ($shippingAddress->getStreet()) {
                        $shippingAddress->setStreet($street);
                    }
                    if ($shippingAddress->getCity()) {
                        $shippingAddress->setCity($city);
                    }
                    if ($shippingAddress->getPostcode()) {
                        $shippingAddress->setPostcode($postcode);
                    }
                    if ($shippingAddress->getTelephone()) {
                        $shippingAddress->setTelephone($telephone);
                    }
                    if ($shippingAddress->getVatId()) {
                        $shippingAddress->setVatId($vatId);
                    }
                    if ($shippingAddress->getCompany()) {
                        $shippingAddress->setCompany($company);
                    }
                    if ($shippingAddress->getCountryId()) {
                        $shippingAddress->setCountryId($countryId);
                    }
                    if ($shippingAddress->getRegion()) {
                        $shippingAddress->setRegion($region);
                    }
                    if ($shippingAddress->getRegionId()) {
                        $shippingAddress->setRegionId($regionId);
                    }
                    if ($shippingAddress->getFax()) {
                        $shippingAddress->setFax($fax);
                    }

                    $shippingAddress->save();
                    $output->writeln("Shipping адрес для гостевого заказа ID: {$shippingAddress->getId()} изменен.");
                }

                // Формирование строк для billing_address и shipping_address
                $billingAddressFormatted = "{$company} {$firstName} {$middleName} {$lastName}, {$street}, {$city}, {$region}, {$postcode}";
                $shippingAddressFormatted = "{$company} {$firstName} {$middleName} {$lastName}, {$street}, {$city}, {$region}, {$postcode}";

                // Обновление данных в таблице sales_shipment_grid для гостевых заказов
                $connection->update(
                    'sales_shipment_grid',
                    [
                        'shipping_name' => "{$firstName} {$middleName} {$lastName}",
                        'billing_name' => "{$firstName} {$middleName} {$lastName}",
                        'customer_name' => "{$firstName} {$middleName} {$lastName}",
                        'customer_email' => $email,
                        'billing_address' => $billingAddressFormatted,
                        'shipping_address' => $shippingAddressFormatted
                    ],
                    ['order_id = ?' => $guestOrder->getId()]
                );
                $output->writeln("Данные по доставке для гостевого заказа ID: {$guestOrder->getId()} обновлены.");

                // Обновление данных в таблице sales_invoice_grid
                $connection->update(
                    'sales_invoice_grid',
                    [
                        'customer_name' => "{$firstName} {$middleName} {$lastName}",
                        'billing_name' => "{$firstName} {$middleName} {$lastName}",
                        'customer_email' => $email,
                        'billing_address' => $billingAddressFormatted,
                        'shipping_address' => $shippingAddressFormatted
                    ],
                    ['order_id = ?' => $guestOrder->getId()]
                );
                $output->writeln("Инвойс для гостевого заказа ID: {$guestOrder->getId()} обновлен.");

                // Обновление данных в таблице sales_creditmemo_grid
                $connection->update(
                    'sales_creditmemo_grid',
                    [
                        'customer_name' => "{$firstName} {$middleName} {$lastName}",
                        'billing_name' => "{$firstName} {$middleName} {$lastName}",
                        'customer_email' => $email,
                        'billing_address' => $billingAddressFormatted,
                        'shipping_address' => $shippingAddressFormatted
                    ],
                    ['order_id = ?' => $guestOrder->getId()]
                );
                $output->writeln("Кредит-нота для гостевого заказа ID: {$guestOrder->getId()} обновлена.");

                // Обновление данных в таблице quote для гостевого заказа
                $connection->update(
                    'quote',
                    [
                        'customer_firstname' => $firstName,
                        'customer_middlename' => $middleName,
                        'customer_lastname' => $lastName,
                        'customer_email' => $email
                    ],
                    ['entity_id = ?' => $guestOrder->getQuoteId()]  // Используем quote_id для обновления
                );
                $output->writeln("quote для гостевого заказа ID: {$guestOrder->getId()} обновлена.");

                // Обновление данных в таблице quote_address для гостевого заказа
                $connection->update(
                    'quote_address',
                    [
                        'firstname' => $firstName,
                        'middlename' => $middleName,
                        'lastname' => $lastName,
                        'email' => $email,
                        'city' => $city,
                        'postcode' => $postcode,
                        'telephone' => $telephone,
                        'street' => $street,
                        'company' => $company,
                        'country_id' => $countryId,
                        'region' => $region,
                        'region_id' => $regionId,
                        'fax' => $fax
                    ],
                    ['quote_id = ?' => $guestOrder->getQuoteId()]  // Используем quote_id для обновления
                );

                $output->writeln("quote_address для гостевого заказа ID: {$guestOrder->getQuoteId()} обновлена.");

                $output->writeln("quote_address для гостевого заказа ID: {$guestOrder->getId()} обновлена.");

                $guestOrder->save();
                $output->writeln("Гостевой заказ ID: {$guestOrder->getId()} изменен.");
            }

            // Полный реиндекс
            $output->writeln('<info>Запуск полного реиндекса...</info>');
            $this->indexerProcessor->reindexAll();
            $output->writeln('<info>Полный реиндекс завершен.</info>');

        } catch (LocalizedException $e) {
            $output->writeln('<error>Ошибка: ' . $e->getMessage() . '</error>');
        }

        return Cli::RETURN_SUCCESS;
    }
}
