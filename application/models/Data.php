<?php

/**
 * Class Data
 */
class Data extends CI_Model
{
	/**
	 * @var bool|int
	 */
	private $time = false;

	/**
	 * Data constructor.
	 */
	public function __construct()
	{
		$this->load->library('PHPExcel/IOFactory');
	}

	/**
	 * @param $file
	 *
	 * @return array|void
	 * @throws \PHPExcel_Reader_Exception
	 */
	public function fileContent($file)
	{
		if (!$this->time) {
			return;
		}
		$path       = $this->getDir($file);
		$excel      = IOFactory::load($path);
		$sheet      = $excel->getActiveSheet();
		$highestRow = $sheet->getHighestRow();
		$isHeader   = true;
		$content    = array();
		$cols       = array();

		for ($row = 1; $row <= $highestRow; $row++) {

			$rowData = $this->rowContent($sheet, $row);
			foreach ($rowData as $cells) {

				foreach ($cells as $cellRef => $cellData) {
					if ($cellRef == 'A') {
						continue;
					}
					$cellData = $this->contentRepair($cellData);

					if (!$isHeader) {
						$content[$cells['A']][$cols[$cellRef]] = $cellData;
					} else {
						$cols[$cellRef] = $cellData;
					}
				}

				$isHeader = false;
			}
		}

		return $content;
	}

	/**
	 * @return array
	 */
	public function getNhanSu()
	{
		$lstNs = array();

		try {
			foreach ($this->fileContent('nhanvien.xlsx') as $name => $nv) {
				$lstNs[$name] = new NhanSu($name, $nv);
			}
		} catch (\PHPExcel_Reader_Exception $e) {

		}

		return $lstNs;
	}

	/**
	 * @return array
	 */
	public function nangsuat()
	{
		try {
			return $this->fileContent('nangsuat.xlsx');
		} catch (\PHPExcel_Reader_Exception $e) {
			return array();
		}
	}

	/**
	 * @return array
	 */
	public function chamcong()
	{
		try {
			return $this->fileContent('chamcong.xlsx');
		} catch (\PHPExcel_Reader_Exception $e) {
			return array();
		}
	}

	/**
	 * @return array
	 */
	public function phancong()
	{
		try {
			return $this->fileContent('phancong.xlsx');
		} catch (\PHPExcel_Reader_Exception $e) {
			return array();
		}
	}

	/**
	 * @param $sheet
	 * @param $row
	 *
	 * @return mixed
	 */
	private function rowContent($sheet, $row)
	{
		$highestColumn = $this->getHighestColumn($sheet);

		return $sheet->rangeToArray(
			'A'.$row.':'.$highestColumn.$row,
			null,
			true,
			false,
			true
		);
	}

	/**
	 * @param $sheet
	 *
	 * @return int|string
	 */
	private function getHighestColumn($sheet)
	{
		$highestColumn = $sheet->getHighestColumn();
		$headerRow     = 1;
		$rowsData      = $sheet->rangeToArray('A'.$headerRow.':'.$highestColumn.$headerRow,
											  null,
											  true,
											  false,
											  true);
		foreach ($rowsData as $rowData) {
			foreach ($rowData as $cellRef => $cellOriginData) {
				$cellData = $this->contentRepair($cellOriginData);
				if (empty($cellData)
					|| is_null($cellData)
					|| $cellData == ''
				) {
					continue;
				}
				$highestColumn = $cellRef;
			}
		}

		return $highestColumn;
	}

	/**
	 * @param string $file
	 *
	 * @return string
	 */
	private function getDir($file = '')
	{
		return FCPATH.'Data'.DIRECTORY_SEPARATOR.$this->time.DIRECTORY_SEPARATOR.$file;
	}

	/**
	 * @param string $content
	 *
	 * @return array|null|string|string[]
	 */
	private function contentRepair($content = '')
	{
		$content = explode(PHP_EOL, trim($content));
		$content = array_filter($content, function ($value) {
			return trim($value) !== '';
		});
		$content = implode(', ', $content);
		$content = preg_replace('/[\x00-\x1F\x7F-\xFF]/', '', $content);
		$content = preg_replace('/[\x00-\x1F\x7F]/', '', $content);
		$content = preg_replace('/[\x00-\x1F\x7F]/u', '', $content);
		$content = preg_replace('/[\x00-\x1F\x7F\xA0]/u', '', $content);
		$content = nl2br($content);

		return $content;
	}

	/**
	 * @param $time
	 *
	 * @return $this
	 */
	public function setTime($time)
	{
		$this->time = $time;

		return $this;
	}

	/**
	 * @return bool|int
	 */
	public function getTime()
	{
		return $this->time;
	}
}
