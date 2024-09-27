<?php

/**
 * Copyright © 2003-2024 The Galette Team
 *
 * This file is part of Galette (https://galette.eu).
 *
 * Galette is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Galette is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 *  GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Galette. If not, see <http://www.gnu.org/licenses/>.
 */

declare(strict_types=1);

namespace Galette\IO;

use Galette\Core\Preferences;
use Galette\Core\PrintLogo;
use Analog\Analog;
use Galette\Entity\Adherent;
use Galette\Entity\Status;
use Galette\Repository\Members;

/**
 * Member card PDF adaptative
 *
 * @author Johan Cwiklinski <johan@x-tnd.be>
 * @author Fabrice Santoni <fabrice@santoni.ch>
 */
protected int $avg_ratio;
protected float $ratio;
protected float $wphoto;
protected float $hphoto;
protected string $adh_nbr;
protected float $cell_he;
protected int $ban_max_he;

class PdfMembersCardsAdaptative extends PdfMembersCards
{
    /**
     * Initialize PDF
     *
     * @return void
     */
    public function init(): void
    {
        parent::init();

        //maximum size for visible text. May vary with fonts.
         $this->max_text_size_full = self::getWidth();
        $this->year_font_size = (int)round(self::getWidth() / 7);

        

        $print_logo = new PrintLogo();

        // Set logo size to max 33% width  or max 45% height
        $this->ratio = $print_logo->getWidth() / $print_logo->getHeight();

        if ($this->ratio <= 1) {
            if ($print_logo->getHeight() > 0.45 * $this->he * 3.78) {
                $this->hlogo = round(0.45 * $this->he,0,PHP_ROUND_HALF_DOWN);
            } else {
                // Convert original pixels size to millimeters
                $this->hlogo = $print_logo->getHeight() / 3.78;
            }
            $this->wlogo = round($this->hlogo *  $this->ratio);
        } else {
            if ($print_logo->getWidth() > 0.33 * $this->wi * 3.78) {
                $this->wlogo = round(0.33 * $this->wi,0,PHP_ROUND_HALF_DOWN);
            } else {
                // Convert original pixels size to millimeters
                $this->wlogo = $print_logo->getWidth() / 3.78;
            }
            $this->hlogo = round($this->wlogo / $this->ratio);
        }
    }

    /**
     * Draw members cards
     *
     * @param array<Adherent> $members Members
     *
     * @return void
     */
    public function drawCards(array $members): void
    {
        global $zdb;

        $nb_card = 0;

        $status = new Status($zdb);
        $status_list = $status->getCompleteList();

        foreach ($members as $member) {
            // Detect page breaks
            if ($nb_card % ($this->nbcol * $this->nbrow) == 0) {
                $this->AddPage();
            }

            // Compute card position on page
            $col = $nb_card % $this->nbcol;
            $row = (int)(($nb_card / $this->nbcol)) % $this->nbrow;
            // Set origin
            $x0 = $this->xorigin + $col * (round($this->wi) + round($this->hspacing));
            $y0 = $this->yorigin + $row * (round($this->he) + round($this->vspacing));
            // Logo X position
            $xl = round($x0 + $this->wi - $this->wlogo);
            // Baneer max height
		    $this->ban_max_he = 12;
            // Get data
            $email = '';
            switch ($this->preferences->pref_card_address) {
                case 0:
                    $email .= $member->email;
                    break;
                case 5:
                    $email .= $member->zipcode . ' - ' . $member->town;
                    break;
                case 6:
                    $email .= $member->nickname;
                    break;
                case 7:
                    $email .= $member->job;
                    break;
                case 8:
                    $email .= $member->number;
                    break;
            }

            // Select strip color
            $fcol = ['R' => 0, 'G' => 0, 'B' => 0];
            if ($status_list[$member->status]['extra'] <= Members::NON_STAFF_MEMBERS) {
                $fcol = $this->bcol;
            } elseif (
                $member->status == 5 /*Benefactor member*/
                || $member->status === 6 /*Founder member*/
            ) {
                $fcol = $this->hcol;
            } elseif ($member->isActive()) {
                $fcol = $this->scol;
            }

            $nom_adh_ext = '';
            if ($this->preferences->pref_bool_display_title) {
                $nom_adh_ext .= $member->stitle . ' ';
            }
            $nom_adh_ext .= $member->sname;
            $photo = $member->picture;
            $photofile = $photo->getPath();

            // Photo 100x130 and logo
            $this->wphoto =  round($this->wi / 3.5);
			$this->hphoto =  $this->wphoto * 1.30;
            $this->Image($photofile, $x0 + 1, $y0 + 1, $this->wphoto );
            $this->Image($this->logofile, $xl - 1, $y0 + 1, round($this->wlogo));
            // Define max space for text
			$this->max_text_size_top = $this->max_text_size_full - intval($this->wlogo + $this->wphoto);
			$this->max_text_size_center = $this->max_text_size_full - intval($this->wphoto);

            // Color=#8C8C8C: Shadow of the year
            $this->SetTextColor(140);
            $this->SetFontSize($this->year_font_size);

            $an_cot = $this->an_cot;
            if ($an_cot === 'DEADLINE') {
                //get current member deadline
                $an_cot = $member->due_date;
            }

            $xan_cot = $x0 + $this->wi / 2 - $this->GetStringWidth(
                $an_cot,
                self::FONT,
                'B',
                $this->year_font_size
            ) / 2 ;
            $this->SetXY($xan_cot, $y0 + 1);
            $this->writeHTML('<strong>' . $an_cot . '</strong>', false, false);
            // Colored Text (Big label, id, year)
            $this->SetTextColor($fcol['R'], $fcol['G'], $fcol['B']);

            $this->SetFontSize(8);
            $this->SetFont(self::FONT,'B');
            $this->ratio = $this->wlogo / $this->hlogo;
           if (!empty($this->preferences->pref_show_id) || !empty($member->number)) {
                $member_id = (!empty($member->number)) ? $member->number : $member->id;
				$this->adh_nbr = _T("Member") . ' n° : ' . $member_id ;
               if ($this->hlogo + 1 > 7 ) {
				$this->fixSize(
                $this->adh_nbr,
                $this->max_text_size_top - 3 ,
                8,
                'B'
            	);
					$this->SetFontSize(round($this->FontSizePt,0,PHP_ROUND_HALF_DOWN));
					$xid = $x0 + $this->wi / 2 - $this->GetStringWidth($this->adh_nbr,$this->FontFamily, $this->FontStyle, $this->FontSizePt) / 2 + $this->wphoto / 2 - $this->wlogo / 2;
					$this->SetXY($xid, $y0 + 8) ;
						} else {
						$this->fixSize(
                		$this->adh_nbr,
                		$this->max_text_size_center,
                		10,
                		'B'
            			);
					$xid = $x0 + $this->wi / 2 - $this->GetStringWidth($this->adh_nbr, $this->FontFamily, $this->FontStyle, $this->FontSizePt) / 2 + $this->wphoto / 2;
					$this->SetXY($xid, $y0 + 8) ;
               }
				$this->SetFontSize(round($this->FontSizePt,0,PHP_ROUND_HALF_DOWN));
				$this->writeHTML('<strong>' . $this->adh_nbr . '  </strong>', false, false);
           }
            
            $this->SetFontSize($this->year_font_size);
            $xan_cot = $xan_cot - 0.1;
            $this->SetXY($xan_cot, $y0 + 1 - 0.1);
            $this->writeHTML('<strong>' . $an_cot . '</strong>', false, false);

            // Abbrev: Adapt font size to text length
            if ( 13 < $this->hlogo + 1 ) {
				            $this->fixSize(
                $this->abrev,
                $this->max_text_size_top - 4,
                12,
                'B'
            );
				
				$this->SetFontSize(round($this->FontSizePt,0,PHP_ROUND_HALF_DOWN)) ;
				$xid = $x0 + $this->wi / 2 - $this->GetStringWidth($this->abrev, $this->FontFamily, $this->FontStyle,$this->FontSizePt) / 2 + $this->wphoto/2 - $this->wlogo/2  ;
			} else {
				$this->fixSize(
                $this->abrev,
                $this->max_text_size_center,
                12,
                'B'
            );
				$this->SetFontSize(round($this->FontSizePt,0,PHP_ROUND_HALF_DOWN));	
            	$xid = $x0 + $this->wi / 2 - $this->GetStringWidth($this->abrev, $this->FontFamily, $this->FontStyle,$this->FontSizePt) / 2 + $this->wphoto/2 ;
				}
            $this->SetXY($xid , $y0 + 13);
			$this->writeHTML('<strong>' . $this->abrev . '</strong>', true, false);

            // Name: Adapt font size to text length
            $this->SetTextColor(0);
            $this->fixSize(
                $nom_adh_ext,
                $this->max_text_size,
                8,
                'B'
            );
            if ($this->hlogo + 2 >= + 20) {
                $this->SetXY($x0 + $this->wphoto + 2, $y0 + $this->hlogo + 2 + 1);
            } else {
                $this->SetXY($x0 + $this->wphoto + 2, $y0 + 20);
            }
            $this->SetFontSize(round($this->FontSizePt,0,PHP_ROUND_HALF_DOWN));
			if ($this->FontSizePt <= 7) {
				$this->SetFontSize(7);
			$this->writeHTML('<strong>' . $member->stitle . " " . $member->name . '</strong>' , true, false);
			$this->SetX($x0 + $this->wphoto + 2);
			$this->writeHTML('<strong>' . $member->surname . '</strong>', true, false);
				if ($this->he < 44) {
				$this->ban_max_he = 10 ;
				}
			} else {
            $this->writeHTML('<strong>' . $nom_adh_ext . '</strong>', true, false);
			}

            // Email (adapt too)
            $this->fixSize(
                $email,
                $this->max_text_size_center,
                6,
                'B'
            );
            $this->setX($x0 + $this->wphoto + 2);
            $this->writeHTML('<strong>' . $email . '</strong>', false, false);

            // Lower colored strip with long text
            $this->SetFont(self::FONT,'B');
			 $this->fixSize(
                $this->preferences->pref_card_strip,
                $this->max_text_size_full,
                12,
                'B'
            );
			if ($this->wi < 73) {
				$this->SetFontSize(4.5);
			}
					
			$this->SetFontSize(round($this->FontSizePt,0,PHP_ROUND_HALF_DOWN));
    
            $this->SetFillColor($fcol['R'], $fcol['G'], $fcol['B']);
            $this->SetTextColor(
                $this->tcol['R'],
                $this->tcol['G'],
                $this->tcol['B']
            );
           $this->cell_he = ($this->he - round($this->hphoto)-2);
			if ( $this->cell_he >=$this->ban_max_he) {
				$this->cell_he = $this->ban_max_he ;
			}
			
            $this->SetXY($x0, $y0 + $this->he - $this->cell_he);
            $this->Cell(
                $this->wi,
                $this->cell_he,
                $this->preferences->pref_card_strip ,
				0,
                0,
                'C',
                true
            );

            // Draw a gray frame around the card
            $this->Rect($x0, $y0, $this->wi, $this->he);
            $nb_card++;
        }
    }

    /**
     * Get card width
     *
     * @return integer
     */
    public static function getWidth(): int
    {
        global $preferences;

        return $preferences->pref_card_hsize;
    }

    /**
     * Get card height
     *
     * @return integer
     */
    public static function getHeight(): int
    {
        global $preferences;

        return $preferences->pref_card_vsize ;
    }

    /**
     * Get number of columns
     *
     * @return integer
     */
    public static function getCols(): int
    {
        global $preferences;

        $margins = $preferences->pref_card_marges_h * 2;

        $nbcols = (int)round(
            ((self::PAGE_WIDTH - $margins) / $preferences->pref_card_hsize),
            0,
            PHP_ROUND_HALF_DOWN
        );
        if ((($nbcols - 1) * $preferences->pref_card_hspace + $margins + $preferences->pref_card_hsize *  $nbcols) > self::PAGE_WIDTH) {
            --$nbcols;
        }

        return $nbcols;
    }

    /**
     * Get number of rows
     *
     * @return integer
     */
    public static function getRows(): int
    {
        global $preferences;

        $margins = $preferences->pref_card_marges_v * 2;

        $nbrows = (int)round(
            ((self::PAGE_HEIGHT - $margins) / $preferences->pref_card_vsize),
            0,
            PHP_ROUND_HALF_DOWN
        );
        if ((($nbrows - 1) * $preferences->pref_card_vspace + $margins + $preferences->pref_card_vsize *  $nbrows) > self::PAGE_HEIGHT) {
            --$nbrows;
        }

        return $nbrows;
    }
}
