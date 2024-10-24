<?php

namespace Xpendi\CnabBoleto\Boleto\Banco;

use Xpendi\CnabBoleto\Boleto\AbstractBoleto;
use Xpendi\CnabBoleto\CalculoDV;
use Xpendi\CnabBoleto\Contracts\Boleto\Boleto as BoletoContract;
use Xpendi\CnabBoleto\Util;

class Ourinvest extends AbstractBoleto implements BoletoContract
{
    /**
     * Código do banco
     * @var string
     */
    protected $codigoBanco = self::COD_BANCO_OURINVEST;
    /**
     * Define as carteiras disponíveis para este banco
     * @var array
     */
    protected $carteiras = false;
    /**
     * Espécie do documento, código para remessa do CNAB240
     * @var string
     */
    protected $especiesCodigo = [
        'DM'  => '01', //Duplicata Mercantil
        'NP'  => '02', //Nota Promissória
        'DS'  => '12', //Duplicata de Serviço
        'O'   => '99',  //Outros,
    ];
    /**
     * Linha de local de pagamento
     *
     * @var string
     */
    protected $localPagamento = 'Canais eletrônicos, agências ou correspondentes bancários de todo o BRASIL';

    /**
     * Gera o Nosso Número.
     *
     * @throws \Exception
     * @return string
     */
    protected function gerarNossoNumero()
    {
        return $this->isEmissaoPropria() === 'true'
            ? Util::numberFormatGeral($this->getNumero(), 11) . CalculoDV::ourinvestNossoNumero($this->getCarteira(), $this->getNumero())
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

        $campoLivre = Util::numberFormatGeral($this->getAgencia(), 4);
        $campoLivre .= Util::numberFormatGeral($this->getCarteira(), 2);
        $campoLivre .= Util::numberFormatGeral($this->getNossoNumero(), 11);
        $campoLivre .= Util::numberFormatGeral($this->getConta(), 7);
        $campoLivre .= '0';

        return $this->campoLivre = $campoLivre;
    }

    /**
     * Método onde qualquer boleto deve extender para gerar o código da posição de 20 a 44
     *
     * @param $campoLivre
     *
     * @return array
     */
    static public function parseCampoLivre($campoLivre)
    {
        return [
            'convenio' => null,
            'parcela' => null,
            'agenciaDv' => null,
            'contaCorrente' => substr($campoLivre, 16, 7),
            'modalidade' => null,
            'contaCorrenteDv' => null,
            'nossoNumeroDv' => substr($campoLivre, 15, 1),
            'agencia' => substr($campoLivre, 0, 4),
            'nossa_carteira' => substr($campoLivre, 4, 2),
            'codigoCliente' =>  null,
            'nossoNumero' => substr($campoLivre, 6, 10),
            'nossoNumeroFull' => substr($campoLivre, 6, 11),
        ];
    }

    /**
     * @return string
     */
    public function getAgenciaCodigoBeneficiario()
    {
        return sprintf('%04s-%s / %07s-%s', $this->getAgencia(), CalculoDV::ourinvestAgencia($this->getAgencia()), $this->getConta(), CalculoDV::ourinvestConta($this->getConta()));
    }
}
