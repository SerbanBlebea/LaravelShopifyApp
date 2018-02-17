<?php

namespace App\Models;

use App\Interfaces\AuthInterface;
use App\Models\ShopifyApi;
use App\Models\Payment;
use Exception;
use App;

class PaymentHandler
{
    private $api = null;

    public function __construct(AuthInterface $api)
    {
        $this->api = $api->getApi();
    }

    public function charge(Payment $payment)
    {
        $charge = [
            "name"       => $payment->payment_name,
            "price"      => $payment->payment_price,
            "return_url" => config('app.url') . $payment->payment_callback,
            "test"       => (App::environment("local")) ? true : false
        ];

        switch($payment->payment_type)
        {
            case "recurring_charge":
                $response = $this->api->RecurringApplicationCharge->post($charge);
                break;
            case "one_time_charge":
                $response = $this->api->ApplicationCharge->post($charge);
                break;
            case "usage_charge":
                // TODO build usage charge
                break;
            default:
                throw new Exception("Type of charge was not found", 1);
        }

        if($response["status"] == "pending")
        {
            return $response["confirmation_url"];
        } else {
            throw new Exception("Charge didn't go throught", 1);
        }
    }

    public function getAllRecurringCharges()
    {
        return $this->api->RecurringApplicationCharge->get();
    }

    public function activateRecurringCharge(String $id)
    {
        return $this->api->RecurringApplicationCharge($id)->activate();
    }
}
