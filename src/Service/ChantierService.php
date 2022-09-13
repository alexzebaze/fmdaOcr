<?php
namespace App\Service;

use Symfony\Component\Config\Definition\Exception\Exception;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
//use Symfony\Component\Templating\EngineInterface;
use Carbon\Carbon;
use App\Entity\Chantier;

class ChantierService{
    
    public function __construct(EntityManagerInterface $em) {
        $this->em = $em;
    }

    public function getDataHoraire($annee, $mois, $feries, $max, $horaires, $start = 1, $bool = false){
        $k = 0;
        for ($i = $start; $i <= $max; $i++) {
            if (Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd') == "dimanche") {
                continue;
            }

            # Les Lundi, on fait un autre array
            if ($i > 1 && Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd') == "lundi") {
                $k++;
            }

            # API ferié
            $jour = Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd Do MMMM YYYY');
            foreach ($feries as $ferie) {
                if ($ferie->date == "$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i)) {
                    $jour = '<strong>' . $jour . ' - FERIE - ' . $ferie->nom_jour_ferie . '</strong>';
                }
            }

            # Récupération des données du jour
            $m[$k][$i] = array(
                'timestamp' => Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->timestamp,
                'jour' => $jour,
                'heures' => $this->checkDate("$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i), $horaires),
                'jour_numerique' => Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->format('Y-m-d'),
                'fictif' => $this->checkFictif("$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i), $horaires),
                'time' => $this->checkTime("$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i), $horaires),
                'absence' => $this->checkFictifOrAbsence("$annee-" . ((int)$mois < 10 ? "0" . (int)$mois : $mois) . "-" . ($i < 10 ? "0$i" : $i), $horaires),
                'mois_precedent' => $bool,
            );
            if (Carbon::parse("$annee-$mois-" . ($i < 10 ? "0$i" : $i))->locale('fr')->isoFormat('dddd') == "samedi") {
                if (count($m[$k][$i]['heures']) == 0) {
                    unset($m[$k][$i]);
                }
            }
        }
        # nettoyage de l'array
        $m = array_filter($m, function ($value) {
            return !empty($value);
        });
        return $m;
    }

    public function checkDate($date, $horaires)
    {
        if (!$horaires)
            return array();

        $heure = array();
        /** @var Horaire $horaire */
        foreach ($horaires as $horaire) {
            # si la date de fin est setté si oui, on peut lancer la comparaison
            if ($horaire->getDateend() && (int)Carbon::parse($date)->format('Y') <= (int)$horaire->getDateend()->format('Y') && Carbon::parse($date)->between($horaire->getDatestart()->format('Y-m-d 00:00:00'), $horaire->getDateend()->format('Y-m-d 23:59:59'))) {
                $heure[] = array(
                    'time' => $horaire->getTime(),
                    'fictif' => $horaire->getFictif(),
                    'chantier' => $horaire->getChantierid() ? $this->em->getRepository(Chantier::class)->find($horaire->getChantierid()) : '',
                    'id' => $horaire->getIdsession(),
                    'absence' => $horaire->getAbsence(),
                );
            } else {
                if ($date == $horaire->getDatestart()->format('Y-m-d')) {
                    $heure[] = array(
                        'time' => $horaire->getTime(),
                        'fictif' => $horaire->getFictif(),
                        'chantier' => $horaire->getChantierid() ? $this->em->getRepository(Chantier::class)->find($horaire->getChantierid()) : '',
                        'id' => $horaire->getIdsession(),
                        'absence' => $horaire->getAbsence(),
                    );
                }
            }
        }
        return $heure;
    }

    public function checkFictif($date, $horaires)
    {
        if (!$horaires)
            return 0;
        $time = 0;
        /** @var Horaire $horaire */
        foreach ($horaires as $horaire) {
            # si la date de fin est setté si oui, on peut lancer la comparaison
            if ($horaire->getDateend() && (int)Carbon::parse($date)->format('Y') <= (int)$horaire->getDateend()->format('Y') && Carbon::parse($date)->between($horaire->getDatestart()->format('Y-m-d 00:00:00'), $horaire->getDateend()->format('Y-m-d 23:59:59'))) {
                $time = $horaire->getFictif() + $time;
            } else {
                if ($date == $horaire->getDatestart()->format('Y-m-d')) {
                    $time = $horaire->getFictif() + $time;
                }
            }
        }
        return $time;
    }

    
    public function checkTime($date, $horaires)
    {
        if (!$horaires)
            return 0;
        $time = 0;
        /** @var Horaire $horaire */
        foreach ($horaires as $horaire) {
            # si la date de fin est setté si oui, on peut lancer la comparaison
            if ($horaire->getDateend() && (int)Carbon::parse($date)->format('Y') <= (int)$horaire->getDateend()->format('Y') && Carbon::parse($date)->between($horaire->getDatestart()->format('Y-m-d 00:00:00'), $horaire->getDateend()->format('Y-m-d 23:59:59'))) {
                $time = $horaire->getTime() + $time;
            } else {
                if ($date == $horaire->getDatestart()->format('Y-m-d')) {
                    $time = $horaire->getTime() + $time;
                }
            }
        }
        return $time;
    }

    public function checkFictifOrAbsence($date, $horaires)
    {
        if (!$horaires)
            return 0;
        $time = 0;
        $absence = null;
        /** @var Horaire $horaire */
        foreach ($horaires as $horaire) {
            # si la date de fin est setté si oui, on peut lancer la comparaison
            if ($horaire->getDateend() && (int)Carbon::parse($date)->format('Y') <= (int)$horaire->getDateend()->format('Y') && Carbon::parse($date)->between($horaire->getDatestart()->format('Y-m-d 00:00:00'), $horaire->getDateend()->format('Y-m-d 23:59:59'))) {
                if ($horaire->getAbsence() == 0) {
                    $time = $horaire->getFictif() + $time;
                } else {
                    if ($horaire->getAbsence() == 1) {
                        $absence['motif'] = "Congés Payés";
                    } elseif ($horaire->getAbsence() == 2) {
                        $absence['motif'] = "En arrêt";
                    } elseif ($horaire->getAbsence() == 3) {
                        $absence['motif'] = "Chômage Partiel";
                    } elseif ($horaire->getAbsence() == 4) {
                        $absence['motif'] = "Absence";
                    } elseif ($horaire->getAbsence() == 5) {
                        $absence['motif'] = "Formation";
                    } elseif ($horaire->getAbsence() == 6) {
                        $absence['motif'] = "RTT";
                    } elseif ($horaire->getAbsence() == 7) {
                        $absence['motif'] = "Férié";
                    }
                    $absence['id'] = $horaire->getAbsence();
                }
            } else {
                if ($date == $horaire->getDatestart()->format('Y-m-d')) {
                    if ($horaire->getAbsence() == 0) {
                        $time = $horaire->getFictif() + $time;
                    } else {
                        if ($horaire->getAbsence() == 1) {
                            $absence['motif'] = "Congés Payés";
                        } elseif ($horaire->getAbsence() == 2) {
                            $absence['motif'] = "En arrêt";
                        } elseif ($horaire->getAbsence() == 3) {
                            $absence['motif'] = "Chômage Partiel";
                        } elseif ($horaire->getAbsence() == 4) {
                            $absence['motif'] = "Absence";
                        } elseif ($horaire->getAbsence() == 5) {
                            $absence['motif'] = "Formation";
                        } elseif ($horaire->getAbsence() == 6) {
                            $absence['motif'] = "RTT";
                        } elseif ($horaire->getAbsence() == 7) {
                            $absence['motif'] = "Férié";
                        }
                        $absence['id'] = $horaire->getAbsence();
                    }
                }
            }
        }

        if ($absence !== null && $time == 0) {
            return $absence;
        } else {
            return $time;
        }

    }
    
}
