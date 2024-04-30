<?php
namespace Xpendi\CnabBoleto\Boleto\Banco;

use Xpendi\CnabBoleto\Boleto\AbstractBoleto;
use Xpendi\CnabBoleto\CalculoDV;
use Xpendi\CnabBoleto\Contracts\Boleto\Boleto as BoletoContract;
use Xpendi\CnabBoleto\Util;

class Safra extends AbstractBoleto implements BoletoContract
{

    /**
     * Local de pagamento
     *
     * @var string
     */
    protected $localPagamento = 'Até o vencimento, preferencialmente no Safra';

    /**
     * Código do banco
     *
     * @var string
     */
    protected $codigoBanco = self::COD_BANCO_SAFRA;
    /**
     * Variáveis adicionais.
     *
     * @var array
     */
    public $variaveis_adicionais = [
        'carteira_nome' => '',
    ];
    /**
     * Define as carteiras disponíveis para este banco
     *
     * @var array
     */
    protected $carteiras = [
        '1', // Cobrança Simples
        '2' // Cobrança Vinculada
    ];
    /**
     * Espécie do documento, coódigo para remessa
     *
     * @var string
     */
    protected $especiesCodigo = [
        'DM' => '01', // Duplicata Mercantil
        'NP' => '02', // Nota Promissória
        'NS' => '03', // Nota de Seguro
        'REC' => '05', // Recibo
        'DS' => '09', // Duplicata de Serviço
        'BCC' => '31', // CARTAO DE CREDITO
    ];
    /**
     * Seta dias para baixa automática
     *
     * @param int $baixaAutomatica
     *
     * @return $this
     * @throws \Exception
     */
    public function setDiasBaixaAutomatica($baixaAutomatica)
    {
        if ($this->getDiasProtesto() > 0) {
            throw new \Exception('Você deve usar dias de protesto ou dias de baixa, nunca os 2');
        }
        $baixaAutomatica = (int) $baixaAutomatica;
        $this->diasBaixaAutomatica = $baixaAutomatica > 0 ? $baixaAutomatica : 0;
        return $this;
    }

    /**
     * Gera o Nosso Número.
     *
     * @return string
     * @throws \Exception
     */
    protected function gerarNossoNumero()
    {
        if ($this->isEmissaoPropria() === true) {
            $numero_boleto = Util::numberFormatGeral($this->getNumero(), 8);
            $carteira = Util::numberFormatGeral($this->getCarteira(), 3);
            $agencia = Util::numberFormatGeral($this->getAgencia(), 4);
            $conta = Util::numberFormatGeral($this->getConta(), 5);
            $dv = CalculoDV::itauNossoNumero($agencia, $conta, $carteira, $numero_boleto);
            return $numero_boleto . $dv;
        } else {
            return Util::numberFormatGeral(0, 12);
        }
    }
    /**
     * Método que retorna o nosso numero usado no boleto. alguns bancos possuem algumas diferenças.
     *
     * @return string
     */
    public function getNossoNumeroBoleto()
    {
        return $this->isEmissaoPropria() === true 
            ? $this->getCarteira() . '/' . substr_replace($this->getNossoNumero(), '-', -1, 0)
            : Util::numberFormatGeral(0, 12);
    }
    /**
     * Método para gerar o código da posição de 20 a 44
     *
     * @return string
     * @throws \Exception
     */
    protected function getCampoLivre()
    {
        if ($this->campoLivre) {
            return $this->campoLivre;
        }

        $campoLivre = Util::numberFormatGeral($this->getCarteira(), 3);
        $campoLivre .= Util::numberFormatGeral($this->getNossoNumero(), 9);
        $campoLivre .= Util::numberFormatGeral($this->getAgencia(), 4);
        $campoLivre .= Util::numberFormatGeral($this->getConta(), 5);
        $campoLivre .= CalculoDV::itauContaCorrente($this->getAgencia(), $this->getConta());
        $campoLivre .= '000';

        return $this->campoLivre = $campoLivre;
    }

    /**
     * Método onde qualquer boleto deve extender para gerar o código da posição de 20 a 44
     *
     * @param $campoLivre
     *
     * @return array
     */
    public static function parseCampoLivre($campoLivre) {
        return [
            'convenio' => null,
            'agenciaDv' => null,
            'codigoCliente' => null,
            'carteira' => substr($campoLivre, 0, 3),
            'nossoNumero' => substr($campoLivre, 3, 8),
            'nossoNumeroDv' => substr($campoLivre, 11, 1),
            'nossoNumeroFull' => substr($campoLivre, 3, 9),
            'agencia' => substr($campoLivre, 12, 4),
            'contaCorrente' => substr($campoLivre, 16, 5),
            'contaCorrenteDv' => substr($campoLivre, 21, 1),
        ];
    }
    /**
     * Método que retorna o digito da conta do Itau
     *
     * @return int
     */
    public function getContaDv(){
        if($this->contaDv !== NULL)
            return $this->contaDv;
        return  CalculoDV::itauContaCorrente($this->getAgencia(), $this->getConta());
    }
}
