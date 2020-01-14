<?php

/**
 * PHP Boleto ZF2 - Versão Beta
 *
 * Este arquivo está disponível sob a Licença GPL disponível pela Web
 * em http://pt.wikipedia.org/wiki/GNU_General_Public_License
 * Você deve ter recebido uma cópia da GNU Public License junto com
 * este pacote; se não, escreva para:
 *
 * Free Software Foundation, Inc.
 * 59 Temple Place - Suite 330
 * Boston, MA 02111-1307, USA.
 *
 * Originado do Projeto BoletoPhp: http://www.boletophp.com.br
 *
 * Adaptação ao Zend Framework 2: João G. Zanon Jr. <jot@jot.com.br>
 *
 */

namespace PhpBoletoZf2\Factory;

use Zend\Hydrator\ClassMethods;
use PhpBoletoZf2\Factory\AbstractBoletoFactory;
use PhpBoletoZf2\Lib\Util;
use Zend\Barcode\Barcode;

class BB extends AbstractBoletoFactory
{

    protected $codigoBanco = '001';

    public function prepare()
    {

        /**
         * adicionando dados das instruções e demonstrativo no boleto
         */
        (new ClassMethods())->hydrate($this->config['php-zf2-boleto']['instrucoes'], $this->getBoleto());

        /**
         * Calcula o fator do vencimento (número inteiro que representa a data de vencimento na linha digitavel)
         */
        $fatorVencimento = Util::fatorVencimento($this->getBoleto()->getDataVencimento()->format("d/m/Y"));

        /**
         * Processando o valor para aplicação na linha digitável e no código de barras
         */
        $valor           = preg_replace(
            "/[^0-9]/",
            "",
            $this->getBoleto()->getValor()
        ); // removendo formatação do número
        $valorProcessado = \str_pad($valor, 10, '0', STR_PAD_LEFT);
        $carteira        = \str_pad($this->getBanco()->getCarteira(), 2, '0', STR_PAD_LEFT);
        $agencia         = \str_pad($this->getCedente()->getAgencia(), 4, '0', STR_PAD_LEFT);
        $conta           = \str_pad($this->getCedente()->getContaCorrente(), 8, '0', STR_PAD_LEFT);
        $banco           = \str_pad($this->getBanco()->getCodigoBanco(), 3, '0', STR_PAD_LEFT);
        $moeda           = $this->getBanco()->getMoeda();

        /**
         * Formatando os dados bancários do cedente para impressão
         */
        $this->getCedente()->setAgenciaCodigo(
            $this->getCedente()->getAgencia()
            . '-'
            . $this->getCedente()->getAgenciaDv()
            . ' / '
            . $this->getCedente()->getContaCorrente()
            . '-'
            . $this->getCedente()->getContaCorrenteDv()
        );

        // usado quando convenio tem 7 digitos
        $livreZeros = '000000';

        switch ($this->getCedente()->getFormatacaoConvenio()) {
            case 8 :
                $convenioProcessado    = str_pad($this->getCedente()->getConvenio(), 8, '0', STR_PAD_LEFT);
                $nossoNumeroProcessado = str_pad($this->getBoleto()->getNossoNumero(), 9, '0', STR_PAD_LEFT);

                $DV = Util::modulo11(
                    $banco
                    . $moeda
                    . $fatorVencimento
                    . $valorProcessado
                    . $livreZeros
                    . $convenioProcessado
                    . $nossoNumeroProcessado
                    . $carteira,
                    9,
                    2
                );

                $strLinha = $banco
                    . $moeda
                    . $DV
                    . $fatorVencimento
                    . $valorProcessado
                    . $livreZeros
                    . $convenioProcessado
                    . $nossoNumeroProcessado
                    . $this->getBanco()->getCarteira();

                $nossoNumeroFormatado = $convenioProcessado . $nossoNumeroProcessado . '-' . Util::modulo11(
                        $convenioProcessado . $nossoNumeroProcessado
                    );

                break;;
            case 7 :
                $convenioProcessado    = str_pad($this->getCedente()->getConvenio(), 7, '0', STR_PAD_LEFT);
                $nossoNumeroProcessado = str_pad($this->getBoleto()->getNossoNumero(), 10, '0', STR_PAD_LEFT);

                $dadosDv = (
                    $banco . $moeda . $fatorVencimento . $valorProcessado . $livreZeros . $convenioProcessado . $nossoNumeroProcessado . $carteira
                );
                $DV      = Util::modulo11($dadosDv, 9, 2);

                $strLinha = $banco
                    . $moeda
                    . $DV
                    . $fatorVencimento
                    . $valorProcessado
                    . $livreZeros
                    . $convenioProcessado
                    . $nossoNumeroProcessado
                    . $carteira;

                $nossoNumeroFormatado = $convenioProcessado . $nossoNumeroProcessado;

                break;
            case 6 :
                $convenioProcessado = str_pad($this->getCedente()->getConvenio(), 6, '0', STR_PAD_LEFT);

                switch ($this->getBoleto()->getFormatacaoNossoNumero()) {
                    case 1 :
                        $nossoNumeroProcessado = str_pad($this->getBoleto()->getNossoNumero(), 5, '0', STR_PAD_LEFT);
                        $DV                    = Util::modulo11(
                            $banco
                            . $moeda
                            . $fatorVencimento
                            . $valorProcessado
                            . $convenioProcessado
                            . $nossoNumeroProcessado
                            . $agencia
                            . $conta
                            . $carteira,
                            9,
                            2
                        );

                        $strLinha = $banco
                            . $moeda
                            . $DV
                            . $fatorVencimento
                            . $valorProcessado
                            . $convenioProcessado
                            . $nossoNumeroProcessado
                            . $agencia
                            . $conta
                            . $carteira;

                        $nossoNumeroFormatado =
                            $convenioProcessado
                            . $nossoNumeroProcessado
                            . '-'
                            . Util::modulo11($convenioProcessado . $nossoNumeroProcessado);
                        break;
                    case 2 :
                        $numeroServico         = 21;
                        $nossoNumeroProcessado = str_pad($this->getBoleto()->getNossoNumero(), 17, '0', STR_PAD_LEFT);
                        $DV                    = Util::modulo11(
                            $banco
                            . $moeda
                            . $fatorVencimento
                            . $valorProcessado
                            . $convenioProcessado
                            . $nossoNumeroProcessado
                            . $numeroServico,
                            9,
                            2
                        );

                        $strLinha = $banco
                            . $moeda
                            . $DV
                            . $fatorVencimento
                            . $valorProcessado
                            . $convenioProcessado
                            . $nossoNumeroProcessado
                            . $numeroServico;

                        $nossoNumeroFormatado = $nossoNumeroProcessado;
                        break;
                }

                break;
        }

        /**
         * Iniciando opções para criação do Código de Barras
         */
        $barcodeOptions = array('text' => $strLinha);

        /**
         * Criando o código de barras em uma imagem e retornando seu base64
         */
        $codigoDeBarras = Barcode::factory(
            'Code25interleaved',
            'PhpBoletoZf2\Lib\Barcode\Renderer\Base64',
            $barcodeOptions,
            array()
        );

        /**
         * Termina de hidratar o objetodo boleto
         */
        $this->getBoleto()
            ->setCodigoDeBarras($codigoDeBarras)
            ->setLinhaDigitavel(Util::montaLinhaDigitavel($strLinha))
            ->setNossoNumeroFormatado($nossoNumeroFormatado);

        return $this;
    }

}
