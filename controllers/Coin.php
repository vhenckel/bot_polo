<?php
defined('BASEPATH') OR exit('No direct script access allowed');

class Coin extends CI_Controller {

    public function __construct()
    {
        parent::__construct();
        $this->load->library('Poloniex');
        $this->load->model('coin/Cotacao_model', 'cotacaoM');
        $this->load->model('coin/Trade_model', 'tradeM');
        $this->load->model('coin/Wallet_model', 'walletM');
    }

    public function index()
    {
        $poloniex = new Poloniex();
        dd($poloniex->get_ticker());
    }

    public function atualiza_cotacao($moeda = 'USDT_LTC')
    {
        $poloniex = new Poloniex();
        for ($i = 0; $i < 30; $i++) {
            $cotacoes = $poloniex->get_ticker();
            foreach ($cotacoes as $indice => $cotacao) {
                if ($indice == $moeda) {
                    unset($cotacao['id']);
                    $cotacao['date'] = date('Y-m-d H:i:s');
                    $this->neural_calculo();
                    $insert = $this->cotacaoM->insert($cotacao);
                }
            }
            sleep(2);
        }
    }

    public function neural_calculo()
    {
        $subindo  = 0;
        $descendo = 0;

        $ultimas = $this->cotacaoM->get_last(120);
        $contador = 0;
        foreach ($ultimas as $cotacao) {
            $ultimo_valor = (int) ($contador == 0) ? $ultimas[0]->high24hr : $ultimas[$contador-1]->last;
            if ($cotacao->last > $ultimo_valor) {
                $subindo++;
                echo $cotacao->last . ' > ' . $ultimo_valor . ' -- subindo: ' . $subindo . '<br>';
            } else if ($cotacao->last < $ultimo_valor) {
                $descendo++;
                echo $cotacao->last . ' < ' . $ultimo_valor . ' -- descendo: ' . $descendo . '<br>';
            }
            $valor_final = $cotacao->last;
            $contador++;
        }
        echo '<hr>';
        $amount_wallet = $this->walletM->get_by_id(1);
        if ($subindo > $descendo) {
            $percent = $valor_final * 0.02;
            if (($valor_final - $percent) > $ultimas[0]->last) {
                echo 'Hora de vender...'  . '<br>';
                echo 'Primeiro valor: ' . $ultimas[0]->last . '<br>';
                echo 'Último valor: ' . $valor_final . '<br>';
                echo '%' . $percent;
                // Se tiver mais de 10 moedas na carteira
                if ($amount_wallet->amount_coin >= 10) {
                    $insert = $this->tradeM->insert(
                        [
                            'value'  => $valor_final,
                            'amount' => 10,
                            'type'   => 'Vender'
                        ]
                    );
                    $update_wallet = $this->walletM->update(1, ['amount_coin' => ($amount_wallet->amount_coin - 10), 'amount_usd' => ($amount_wallet->amount_usd + ($valor_final * 10))]);
                }
            }
        } else if ($descendo > $subindo) {
            $percent = $valor_final * 0.02;
            if (($valor_final + $percent) < $ultimas[0]->last) {
                echo 'Hora de comprar...' . '<br>';
                echo 'Primeiro valor: ' . $ultimas[0]->last . '<br>';
                echo 'Último valor: ' . $valor_final . '<br>';
                echo '%' . $percent;
                // Se tiver dinheiro suficiente na carteira
                if ($amount_wallet->amount_usd >= ($valor_final * 10)) {
                    $insert = $this->tradeM->insert(
                        [
                            'value'  => $valor_final,
                            'amount' => 10,
                            'type'   => 'Comprar'
                        ]
                    );
                   $update_wallet = $this->walletM->update(1, ['amount_coin' => ($amount_wallet->amount_coin + 10), 'amount_usd' => ($amount_wallet->amount_usd - ($valor_final * 10))]);
                }
            }
        } else {
            echo 'Só manter...' . '<br>';
        }
        // echo 'Maior valor de 24 horas: ' . $ultimas[0]->high24hr . '<br>';
        echo '<hr>';
        echo 'Primeiro valor: ' . $ultimas[0]->last . '<br>';
        echo 'Valor agora: ' . $valor_final . '<br>';
    }
}