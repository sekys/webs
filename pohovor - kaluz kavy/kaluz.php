<?php

/**
* Trieda STOL sa stara o reprezentaciu mapy.
* Obsahuje informaice o rozliatej kavy.
*
* @autor: Lukas Sekerak
*/
class Stol {
	protected $data;
	protected $N;
	protected $M;

	// Pocet riadkov a stlpcov
	public function __construct($N, $M) {
		$this->N = $N;
		$this->M = $M;
	}
	
	// Stiahne mapu ako vstupny udaj
	public function generateRandomMap() {
		$html = file_get_contents('http://dazzler.sk/hr/uloha1/');
		if($html == FALSE) {
			throw new Exception;
		}
		echo $html;
		$document = new \DOMDocument();
		@$document->loadHTML($html);
		$content = $document->getElementsByTagName('table')->item(0)->textContent;
		$this->data = array();
		for($start = 0; $start < $this->getSize(); $start += $this->M ) {
			$this->data[] = str_split(substr($content, $start, $this->M));
		}
	}
	
	// Dalej nasleduju pomocne gettre
	public function jeRozliata($r, $s) {
		return $this->data[$r][$s];
	}
	public function getWidth() {
		return $this->M;
	}
	public function getHeight() {
		return $this->N;
	}
	public function getSize() {
		return $this->M * $this->N;
	}
}

class KavaSolver {
	protected $stol;
	protected $kaluze;
	private $ofarbene;
	private $aktualnaKaluz;
	private $prazdnePolicko;

	/**
	*	Trieda riesi problem rozliatej kavy a hlada jednotlive kaluze.
	*	Vypocitava jednotlive velkosti.
	*
	*	Vyuziva sa pri tom SplFixedArray co je len pole. Test performance:
	*	http://blog.shay.co/phps-native-array-vs-splfixedarray-performance
	*/
	public function __construct(Stol $stol) {
		$this->stol = $stol;		
		$this->aktualnaKaluz = -1;
		$this->kaluze = array();
		$this->prazdnePolicko = TRUE;
		for($i=0; $i < $this->stol->getHeight(); $i++) {
			$this->ofarbene[] = new SplFixedArray($this->stol->getWidth()); // > 5.3.0
		}
	}
	
	private function dalsiaRozliataKava($r, $s) {
		if($this->stol->jeRozliata($r, $s) == TRUE && $this->ofarbene[$r][$s] == FALSE) {
			$this->ofarbene[$r][$s] = TRUE;
			$this->kaluze[$this->aktualnaKaluz]['velkost'] += 1;
			$this->najdenaRozliataKava($r, $s);	
		}
	}

	private function najdenaRozliataKava($r, $s) {
		$sirkaSplnena = ($s != $this->stol->getWidth());
		$vyskaSplnena = ($r != $this->stol->getHeight());
		
		if($vyskaSplnena && $sirkaSplnena) $this->dalsiaRozliataKava($r + 1, $s + 1);
		if($r != 0 && $s != 0) $this->dalsiaRozliataKava($r - 1, $s - 1);
		if($vyskaSplnena && $s != 0) $this->dalsiaRozliataKava($r + 1, $s - 1);
		if($r != 0 && $sirkaSplnena) $this->dalsiaRozliataKava($r - 1, $s + 1);
		if($sirkaSplnena) $this->dalsiaRozliataKava($r, $s + 1); // vpravo
		if($s != 0) $this->dalsiaRozliataKava($r, $s - 1); //vlavo
		if($vyskaSplnena) $this->dalsiaRozliataKava($r + 1, $s); // dole
		if($r != 0) $this->dalsiaRozliataKava($r - 1, $s); // hore
	}

	public function solve() {
		for($r = 0; $r < $this->stol->getHeight(); $r++) {
			for($s = 0; $s < $this->stol->getWidth(); $s++) {
				if($this->stol->jeRozliata($r, $s) && $this->ofarbene[$r][$s] == FALSE) {
					if($this->prazdnePolicko) {
						$this->ofarbene[$r][$s] = TRUE;
						$this->prazdnePolicko = FALSE;
						$this->aktualnaKaluz++;
						$this->kaluze[$this->aktualnaKaluz] = array('pozicia' => $r . ',' . $r, 'velkost' => 1);
					} 
					$this->najdenaRozliataKava($r, $s);
				} else {
					$this->prazdnePolicko = TRUE;
				}
			}
		}
	}
	
	
	/**
	*	Kazka kaluz obsahuje informacie o indexe kde zhruba zacina a o jej velkosti.
	*/
	public function getKaluze() {
		return $this->kaluze;
	}	
	public function sort() {
		usort($this->kaluze, array("KavaSolver", "cmpKaluz"));
	}
	public static function cmpKaluz($a, $b) {
        return ($a['velkost'] > $b['velkost']) ? -1 : +1;
    }
}

echo '<pre>';
$mapa = new Stol(20, 20);
$mapa->generateRandomMap();
$kava = new KavaSolver($mapa);
$kava->solve();
$kava->sort();
$kaluze = $kava->getKaluze();
print_r($kaluze);
echo 'Pocet kaluzi ', count($kaluze);
echo '</pre>';