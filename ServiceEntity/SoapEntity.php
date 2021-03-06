<?php
/**
 * Created by Afroze.S.
 * Date: 15/2/18
 * Time: 11:38 AM
 */

namespace Twentyone\UpdateStock\ServiceEntity;


class SoapEntity
{
    /**
     * @var \SoapClient
     */
    private $soapClient;

    /**
     * @param string $url
     * @return void
     */
    public function setConnection($url){
        $this->soapClient = new \SoapClient($url);
        //var_dump($this->soapClient->__getTypes());die;
    }

    /**
     * @param string $functionName
     * @param array $params
     * @return mixed
     */
    public function callFunction($functionName, $params) {
        if ($this->soapClient) {
            $res = $this->soapClient->__soapCall($functionName, $params);
            return $res;
        }
        return null;
    }

    /**
     * @param int $idAtelier
     * @param string $size
     * @return int|null
     */
    public function checkAvailabilityInAtelier($idAtelier, $size) {
        $availability = null;

        if ($this->soapClient) {
            $params = [
                'ID_ARTICOLO' => $idAtelier,
                'TAGLIA' => $this->getAtelierSize($size)
            ];
            $res = $this->callFunction('DisponibilitaVarianteTaglia', [$params]);
            $availability =  $res->DisponibilitaVarianteTagliaResult;
        }
        return $availability;
    }

    /**
     * @param string $email
     * @param string $firstName
     * @param string $lastName
     * @return mixed|null
     */
    public function updateClient($email, $firstName, $lastName) {
        $res = null;
        if ($this->soapClient) {
            $params = [
                'EMAIL' => $email,
                'NOME' => $firstName,
                'COGNOME' => $lastName,
                'INDIRIZZO' => '',
                'CAP' => '',
                'CITTA' => '',
                'STATO' => '',
                'TEL' => '',
                'ESENTE' => '',
                'CELL' => '',
                'SESSO' => '',
                'FIDELITY' => '',
                'PI' => '.',
                'CODFIS' => '.'
            ];
            $res = $this->callFunction('AggiornaClienteCompletaFidelity', [$params]);
        }
        return $res;
    }

    /**
     * @param string $email
     * @param int $idAtelier
     * @param string $size
     * @param int $orderId
     * @param string $address1
     * @param string $address2
     * @param string $address3
     * @param float $price
     * @param int $qty
     * @return mixed|null
     */
    public function updateOrder($email, $idAtelier, $size, $orderId, $address1, $address2, $address3, $price, $qty) {
        $string = "SALDI=SI|CAMBIO=1|ID_VALUTA=1||PREZZO_LISTINO=85|EMAIL_CLIENTE=".$email."|ID_CLINETE=".$email."|ID_ARTICOLO=".$idAtelier."|TAGLIA=".$this->getAtelierSize($size)."|CODICE=".$orderId."|DESTINAZIONE_RIGA1=".$address1."|DESTINAZIONE_RIGA2=".$address2."|DESTINAZIONE_RIGA3=".$address3."|PREZZO=".$price."|QTA=".$qty;
        $res = null;
        if ($this->soapClient) {
            $params = [
                'ParametriImpegni' => $string
            ];
            $res = $this->callFunction("SetImpegnoEsteso", [$params]);
        }
        return $res;
    }

    /**
     * @param string $size
     * @return string
     */
    private function getAtelierSize($size) {
        if (is_numeric(substr($size, 0, 1)) && !is_numeric(substr($size, strlen($size)-1, 1))) {
            $preNum = substr($size, 0, strlen($size)-2);
            $size = $preNum."½";
        }
        return $size;
    }

    /**
     * @param string $orderStatus
     * @return null|int
     */
    private static function getAtelierStatus($orderStatus) {

        $attributeAtelier = null;
        switch ($orderStatus) {
            case 'processing':
                $attributeAtelier = 30000001;
                break;
            case 'fraud':
                $attributeAtelier = 30000004;
                break;

            case 'pending_payment':
                $attributeAtelier = 30000002;
                break;

            case 'payment_review':
                $attributeAtelier = 30000002;
                break;

            case 'pending':
                $attributeAtelier = 30000002;
                break;

            case 'holded':
                $attributeAtelier = 30000004;
                break;

            case 'complete':
                $attributeAtelier = 30000005;
                break;

            case 'closed':
                $attributeAtelier = 30000004;
                break;

            case 'canceled':
                $attributeAtelier = 30000004;
                break;

            case 'paypay_canceled_reversal':
                $attributeAtelier = 30000004;
                break;

            case 'pending_paypal':
                $attributeAtelier = 30000002;
                break;

            case 'paypal_reversed':
                $attributeAtelier = 30000004;
                break;

        }

        return $attributeAtelier;
    }

    /**
     * @param string $methodPayment
     * @return null|int
     */
    private static function getAtelierMethodPayment($methodPayment) {

        $attributeAtelier = null;
        switch ($methodPayment) {
            case 'Sella':
                $attributeAtelier = 13;
                break;
            case 'Paypal':
                $attributeAtelier = 14;
                break;
            default:
                $attributeAtelier = 14;
                break;
        }

        return $attributeAtelier;
    }

    /**
     * @param string $email
     * @param int $orderId
     * @param float $fare
     * @return string
     */
    public function communicateShippingFare($email, $orderId, $fare) {

      $res = null;
      if ($this->soapClient) {
          $params = [
              'CODICE' => $orderId,
              'EMAIL_CLIENTE' => $email,
              'SPESE_SPEDIZIONE' => $fare
          ];
          $res = $this->callFunction("SetImpegnoSpeseSpedizioneEmail", [$params]);
      }
      return $res;

    }


    /**
     * @param string $email
     * @param int $orderId
     * @param int $status
     * @return string
     */
    public function communicateOrderStatus($email, $orderId, $status) {

      $res = null;
      if ($this->soapClient) {
          $params = [
              'CODICE' => $orderId,
              'EMAIL_CLIENTE' => $email,
              'ID_STATUS' => $this->getAtelierStatus($status)
          ];
          $res = $this->callFunction("SetImpegnoStatusEmail", [$params]);
      }

      if ($res->SetImpegnoStatusEmailResult == "ER") {
          $res = $this->callFunction("GetStatusImpegnoEmail", [$params]);
      }
      return $res;

    }

    /**
     * @param string $email
     * @param int $orderId
     * @param int $methodPayment
     * @return string
     */
    public function communicateOrderPayment($email, $orderId, $methodPayment) {

      $res = null;
      if ($this->soapClient) {
          $params = [
              'CODICE' => $orderId,
              'EMAIL_CLIENTE' => $email,
              'ID_PAGAMENTO' => $this->getAtelierMethodPayment($methodPayment)
          ];
          $res = $this->callFunction("SetImpegnoPagamentoEmail", [$params]);
      }
      return $res;

    }
}
