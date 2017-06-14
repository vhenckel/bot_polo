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
        $percent = 0.02; // Percentual para efetuar compra e venda
        $qtd     = 1;    // Quantidade a ser negociada em cada transação

        $subindo  = 0;
        $descendo = 0;

        $ultimas = $this->cotacaoM->get_last(240);
        $ultimas = array_reverse($ultimas);

        $contador = 0;

        foreach ($ultimas as $cotacao) {
            $ultimo_valor = (int) ($contador == 0) ? $ultimas[0]->high24hr : $ultimas[$contador-1]->last;
            if ($cotacao->last > $ultimo_valor) {
                $pontuacao = $this->pontuacao($ultimo_valor, $cotacao->last);
                $subindo = $subindo + $pontuacao;
                echo $cotacao->last . ' > ' . $ultimo_valor;
                echo ' | Subindo: &nbsp;&nbsp;&nbsp;' . $subindo;
                echo ' | Pontos: ' . $pontuacao . '<br>';
            } else if ($cotacao->last < $ultimo_valor) {
                $pontuacao = $this->pontuacao($cotacao->last, $ultimo_valor);
                $descendo = $descendo + $pontuacao;
                echo $cotacao->last . ' < ' . $ultimo_valor;
                echo ' | Descendo: ' . $descendo;
                echo ' | Pontos: ' . $pontuacao . '<br>';
            }
            $valor_final = $cotacao->last;
            $contador++;
        }
        echo '----------------------------------------------------<br>';
        $amount_wallet = $this->walletM->get_by_id(1);
        if ($subindo > $descendo && $ultimas[0]->last <= $valor_final) {
            $percent_value = $valor_final * $percent;
            if (($valor_final - $percent_value) > $ultimas[0]->last) {
                echo 'Hora de vender...'  . '<br>';
                // Se tiver moedas na carteira
                if ($amount_wallet->amount_coin >= $qtd) {
                    $insert = $this->tradeM->insert(
                        [
                            'value'  => $valor_final,
                            'amount' => $qtd,
                            'type'   => 'Vender'
                        ]
                    );
                    $update_wallet = $this->walletM->update(1, ['amount_coin' => ($amount_wallet->amount_coin - $qtd), 'amount_usd' => ($amount_wallet->amount_usd + ($valor_final * $qtd))]);
                }
            }
        } else if ($descendo > $subindo && $ultimas[0]->last >= $valor_final) {
            $percent_value = $valor_final * $percent;
            if (($valor_final + $percent_value) < $ultimas[0]->last) {
                echo 'Hora de comprar...' . '<br>';
                // Se tiver dinheiro suficiente na carteira
                if ($amount_wallet->amount_usd >= ($valor_final * $qtd)) {
                    $insert = $this->tradeM->insert(
                        [
                            'value'  => $valor_final,
                            'amount' => $qtd,
                            'type'   => 'Comprar'
                        ]
                    );
                   $update_wallet = $this->walletM->update(1, ['amount_coin' => ($amount_wallet->amount_coin + $qtd), 'amount_usd' => ($amount_wallet->amount_usd - ($valor_final * $qtd))]);
                }
            }
        } else {
            echo 'Só manter...' . '<br>';
        }
        // echo 'Maior valor de 24 horas: ' . $ultimas[0]->high24hr . '<br>';
        echo 'Totais: Descendo: ' . $descendo . ' - Subindo: ' . $subindo . '<br>';
        echo 'Primeiro valor: US$' . $ultimas[0]->last . '<br>';
        echo 'Último valor: US$' . $valor_final . '<br>';
        if (isset($percent_value)) {
            echo 'Diferença de US$' . $percent_value;
        }
    }

    private function pontuacao($menor, $maior)
    {
        $porcentagem = 100 - (($menor / $maior) * 100);
        switch ($porcentagem) {
            case ($porcentagem > 0 && $porcentagem <= 1):
                return 1;
                break;
            case ($porcentagem > 1 && $porcentagem <= 3):
                return 3;
                break;
            case ($porcentagem > 3 && $porcentagem <= 7):
                return 6;
                break;
            case ($porcentagem > 7 && $porcentagem <= 10):
                return 10;
                break;
            case ($porcentagem > 10):
                return 50;
                break;
            default:
                return 1;
                break;
        }

    }
}