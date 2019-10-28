<?php

class AlgoritimoGenetico {

    protected $probabilidadeCruzamento = 90;
    protected $probabilidadeMutacao = 10;
    protected $tamanhoPopulacao = 300;
    protected $fromFile = true;

    /**
     * @var array
     */
    protected $rotas;

    /**
     * @var array
     */
    protected $populacao;

    public function __construct()
    {

        echo "Iniciando a população\n";
        $this->_iniciarPopulacao();

        echo "Avaliando indivíduos\n";
        $this->_avaliarIndividuos();

        $k = 1;

        while ($this->populacao[0]['fitness'] > 0){

            $this->_gerarProximaGeracao();
            $k++;

            system('clear');
            echo "\n\n";
            echo "   Geração \e[0;97;42m$k\e[0m - Erros: \e[0;97;41m{$this->populacao[0]['fitness']}\e[0m\n";
            echo "\n\n";
            $this->_imprimirSudoku();

        }

    }

    private function _iniciarPopulacao(){

        $this->populacao = array_map(function(){

            return ['cromossomo' => $this->_gerarCromossomo(), 'fitness' => 0];

        }, range(1, $this->tamanhoPopulacao));

    }

    private function _gerarProximaGeracao(){

        $this->_novaPopulacaoPorRoleta();
//        $this->_novaPopulacaoPorTorneio();
        $this->_avaliarIndividuos();

    }

    private function _imprimirSudoku($sudoku = null){

        if($sudoku == null){

            $sudoku = $this->populacao[0]['cromossomo'];

        }

        $erros = $this->_errosSudoku($sudoku);

        foreach ($sudoku as $i => $arr) {

            if($i % 3 == 0){
                if($i == 0){
                    echo "       0 1 2   3 4 5   6 7 8\n";
                }
                echo "     -------------------------\n";
            }

            foreach ($arr as $j => $num) {

                if($j % 3 == 0){
                    if($j == 0){
                        echo "   $i | ";
                    } else {
                        echo " |";
                    }
                }

                if(in_array("$i-$j", $erros)) $num = "\e[0;97;41m$num\e[0m";

                if($j == 0) {
                    echo "$num";
                } elseif($j == 8) {
                    echo " $num |";
                } else {
                    echo " $num";
                }

            }

            echo "\n";

        }

        echo "     -------------------------\n";

    }

    private function _novaPopulacaoPorRoleta(){

        $novaPopulacao = [];

        /**
         * Mantendo o melhor da população anterior
         */
        $novaPopulacao[] = $this->populacao[0];

        $umTerco = $this->tamanhoPopulacao/3;
        $inicio = 0;

        while(count($novaPopulacao) < $this->tamanhoPopulacao){

            if($inicio >= $umTerco) $inicio = 0;

            $individos = [
                $this->populacao[$inicio],
                $this->populacao[$inicio + $umTerco],
                $this->populacao[$inicio + $umTerco * 2],
//                $this->populacao[random_int(0, $this->tamanhoPopulacao - 1)]
            ];

            shuffle($individos);

            $cruzarOuMutar = $this->_cruzarOuMutar();

            if($cruzarOuMutar == 'C'){

                $novosIndividuos = $this->_cruzarIndividuos($individos[0], $individos[1]);

            } else {

                $novosIndividuos = $this->_mutarIndividuos($individos[0], $individos[1]);

            }

            $novaPopulacao[] = $novosIndividuos[0];
            $novaPopulacao[] = $novosIndividuos[1];

            $inicio++;

        }

        $this->populacao = $novaPopulacao;

    }

    private function _novaPopulacaoPorTorneio(){

        $novaPopulacao = [];
        $populacao = $this->populacao;
        shuffle($populacao);

        while(count($novaPopulacao) < $this->tamanhoPopulacao/2){

            $individos = array_splice($populacao, 0, 3);

            usort($individos, function($a, $b){
                return $a['fitness'] > $b['fitness'];
            });

            if($this->_cruzarOuMutar() == 'C'){

                $novosIndividuos = $this->_cruzarIndividuos($individos[0], $individos[1]);

            } else {

                $novosIndividuos = $this->_mutarIndividuos($individos[0], $individos[1]);

            }

            $novaPopulacao[] = $novosIndividuos[0];
            $novaPopulacao[] = $novosIndividuos[1];

        }

        /**
         * Mantendo o melhor da população anterior
         */
        $novaPopulacao[] = $this->populacao[0];

        shuffle($this->populacao);

        while(count($novaPopulacao) < $this->tamanhoPopulacao){

            $novaPopulacao[] = array_pop($this->populacao);

        }

        $this->populacao = $novaPopulacao;

    }

    private function _mutarIndividuos($individuo1, $individuo2){

        /**
         * Fazendo a mutação de maneira mais inteligente
         * Gerado um indivíduo aleatório e cruzado com os que vem na função
         * Ou seja, é alterado uma parcela do invíduo aleatóriamente
         */

        $individuoGerado = ['cromossomo' => $this->_gerarCromossomo(), 'fitness' => 0];

        $cruzamento = $this->_cruzarIndividuos($individuo1, $individuoGerado);
        $cruzamento2 = $this->_cruzarIndividuos($individuo2, $individuoGerado);

        return [$cruzamento[0], $cruzamento2[0]];

    }

    private function _cruzarIndividuos($individuo1, $individuo2){

        $sudoku1 = $individuo1['cromossomo'];
        $sudoku2 = $individuo2['cromossomo'];

        if($this->fromFile){
            $sudokuFile = $this->_fromFile();
        }

        $errosSudoku1 = $this->_errosSudoku($sudoku1, $this->fromFile || $individuo1['fitness'] <= 4);
        $errosSudoku2 = $this->_errosSudoku($sudoku2, $this->fromFile || $individuo2['fitness'] <= 4);

        $alteradoAlgo = false;

        foreach ($errosSudoku1 as $erro) {

            list($i, $j) = explode("-", $erro);

            if(!$this->fromFile || $sudokuFile[$i][$j] == 'x'){

                if($sudoku1[$i][$j] != $sudoku2[$i][$j] && !in_array($erro, $errosSudoku2)){

                    $alteradoAlgo = true;
                    $sudoku1[$i][$j] = $sudoku2[$i][$j];

                }

            }

        }

        if(!$alteradoAlgo) $sudoku1 = $this->_gerarCromossomo();

        $alteradoAlgo = false;

        foreach ($errosSudoku2 as $erro) {

            list($i, $j) = explode("-", $erro);

            if(!$this->fromFile || $sudokuFile[$i][$j] == 'x'){

                if($sudoku2[$i][$j] != $sudoku1[$i][$j] && !in_array($erro, $errosSudoku1)){

                    $alteradoAlgo = true;
                    $sudoku2[$i][$j] = $sudoku1[$i][$j];

                }

            }

        }

        if(!$alteradoAlgo) $sudoku2 = $this->_gerarCromossomo();

        $individuo1['cromossomo'] = $sudoku1;
        $individuo2['cromossomo'] = $sudoku2;

        return [$individuo1, $individuo2];

    }

    private function _cruzarOuMutar(){

        $max = $this->probabilidadeCruzamento * 100;
        $max += $this->probabilidadeCruzamento * 100;

        if(random_int(1, $max) > $this->probabilidadeCruzamento * 100){

            return "M";

        }

        return "C";

    }

    private function _avaliarIndividuos(){

        $this->populacao = array_map(function($individuo){

            $individuo['fitness'] = $this->_fitnessFunction($individuo['cromossomo']);

            return $individuo;

        }, $this->populacao);

        $this->_sortPopulacao();

    }

    private function _fromFile(){

        $sudoku = file_get_contents('sudoku');
        $sudoku = explode("\n", trim($sudoku));

        return array_map(function($line){

            return str_split($line);

        }, $sudoku);

    }

    private function _gerarCromossomo(){

        if($this->fromFile){

            $sudoku = $this->_fromFile();

            foreach ($sudoku as $i => $linhas) {

                foreach ($linhas as $j => $num) {

                    if($num == 'x') $sudoku[$i][$j] = random_int(1, 9);

                }

            }

            return $sudoku;

        }

        $sudoku = [];

        foreach (range(1, 9) as $linha) {

            $sudoku[$linha] = [];

            foreach (range(1, 9) as $coluna) {

                $sudoku[$linha][$coluna] = random_int(1, 9);

            }

            shuffle($sudoku[$linha]);

        }

        shuffle($sudoku);

        return $sudoku;

    }

    private function _fitnessFunction($sudoku){

        return count($this->_errosSudoku($sudoku));

    }

    private function _errosSudoku($sudoku, $all = false){

        $erros = [];
        $quadrantes = [];
        $linhas = [];
        $colunas = [];

        foreach (range(0, 8) as $linha) {

            foreach (range(0, 8) as $coluna) {

                if(!isset($colunas[$coluna])) $colunas[$coluna] = [];
                if(!isset($linhas[$linha])) $linhas[$linha] = [];

                $tempPosicao = $sudoku[$linha][$coluna];

                if(in_array($tempPosicao, $colunas[$coluna])){

                    $erros["$linha-$coluna"] = "C";

                    if($all) {
                        foreach (array_keys($colunas[$coluna], $tempPosicao) as $k) {
                            $erros["$k-$coluna"] = 'C';
                        }
                    }

                } elseif(in_array($tempPosicao, $linhas[$linha])){

                    $erros["$linha-$coluna"] = "L";

                    if($all) {
                        foreach (array_keys($linhas[$linha], $tempPosicao) as $k) {
                            $erros["$linha-$k"] = 'L';
                        }
                    }

                }

                $colunas[$coluna][] = $tempPosicao;
                $linhas[$linha][] = $tempPosicao;

                $quadranteColuna = 3;
                $quadranteLinha = 3;

                if($coluna <= 2){
                    $quadranteColuna = 1;
                } elseif($coluna <= 5){
                    $quadranteColuna = 2;
                }

                if($linha <= 2){
                    $quadranteLinha = 1;
                } elseif($linha <= 5){
                    $quadranteLinha = 2;
                }

                if(!isset($quadrantes["$quadranteLinha-$quadranteColuna"])) $quadrantes["$quadranteLinha-$quadranteColuna"] = [];

                if(in_array($tempPosicao, $quadrantes["$quadranteLinha-$quadranteColuna"])){

                    $erros["$linha-$coluna"] = "Q";

                    if($all) {
                        foreach (array_keys($quadrantes["$quadranteLinha-$quadranteColuna"], $tempPosicao) as $k) {
                            $erros[$k] = 'Q';
                        }
                    }

                }

                $quadrantes["$quadranteLinha-$quadranteColuna"]["$linha-$coluna"] = $tempPosicao;

            }

        }

        return array_keys($erros);

    }

    private function _verificarValidade($sudoku){

        $erros = $this->_errosSudoku($sudoku, true);

        var_dump($erros);

        $possibilidades = range(1, 9);

        foreach ($erros as $erro) {

            $linhas = [];
            $colunas = [];
            $quadrantes = [];

            $erro = explode("-", $erro);

            foreach ($possibilidades as $i) {
                $linhas[] = $sudoku[$erro[0]][$i-1];
            }

            foreach ($possibilidades as $i) {
                $colunas[] = $sudoku[$i-1][$erro[1]];
            }

            $qColuna = range(6, 8);
            $qLinha = range(6, 8);

            if($erro[0] <= 2) $qLinha = range(0, 2);
            elseif($erro[0] <= 5) $qLinha = range(3, 5);

            if($erro[1] <= 2) $qColuna = range(0, 2);
            elseif($erro[1] <= 5) $qColuna = range(3, 5);

            foreach ($qLinha as $l) {
                foreach ($qColuna as $c) {
                    $quadrantes[] = $sudoku[$l][$c];
                }
            }

            $linhas = array_unique($linhas);
            $colunas = array_unique($colunas);
            $quadrantes = array_unique($quadrantes);

            $linhas = array_diff($possibilidades, $linhas);
            $colunas = array_diff($possibilidades, $colunas);
            $quadrantes = array_diff($possibilidades, $quadrantes);

            $this->_imprimirSudoku($sudoku);
            var_dump($erros, $linhas, $colunas, $quadrantes);
            die();

        }

    }

    private function _sortPopulacao(){

        usort($this->populacao, function($a, $b){
            return $a['fitness'] > $b['fitness'];
        });

    }

}

new AlgoritimoGenetico();
