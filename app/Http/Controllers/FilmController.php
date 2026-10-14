<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class FilmController extends Controller
{
    /**
     * Display the specified film details.
     */
    public function show(Request $request, string|int $id): Response
    {
        $film = $this->resolveFilm($id);
        $episodes = $this->getEpisodesData();

        return Inertia::render('films/Show', [
            'film' => $film,
            'episodes' => $episodes,
        ]);
    }

    /**
     * Display the specified episode watch page.
     */
    public function watchEpisode(Request $request, string|int $film, string|int $episode): Response
    {
        $filmData = $this->resolveFilm($film);
        $episodes = $this->getEpisodesData();

        // Normalize episode search (e.g. '08', '8', 'EP 08')
        $rawNum = ltrim(str_ireplace('ep', '', (string) $episode), ' 0');
        $epNum = str_pad($rawNum ?: '1', 2, '0', STR_PAD_LEFT);

        $currentEpisode = null;
        foreach ($episodes as $ep) {
            if ($ep['number'] === $epNum || $ep['code'] === (string) $episode || (string) (int) $ep['number'] === (string) (int) $epNum) {
                $currentEpisode = $ep;
                break;
            }
        }

        if (! $currentEpisode) {
            $currentEpisode = $episodes[0];
        }

        $comments = [
            [
                'id' => 1,
                'user' => [
                    'name' => 'WATOON Editorial',
                    'initials' => 'WE',
                    'badge' => 'PRO',
                    'is_verified' => true,
                ],
                'is_pinned' => true,
                'content' => 'Bagus banget episode ini, kualitas produksinya naik drastis.',
                'created_at' => '1d ago',
                'likes' => 128,
                'is_liked' => false,
            ],
            [
                'id' => 2,
                'user' => [
                    'name' => 'Marcus Vance',
                    'initials' => 'MV',
                    'badge' => 'VIP',
                    'is_verified' => false,
                ],
                'is_pinned' => false,
                'content' => 'Akhirnya yang ditunggu-tunggu rilis juga. Worth the wait.',
                'created_at' => '1h ago',
                'likes' => 36,
                'is_liked' => false,
            ],
            [
                'id' => 3,
                'user' => [
                    'name' => 'Elena R.',
                    'initials' => 'ER',
                    'badge' => 'FREE',
                    'is_verified' => false,
                ],
                'is_pinned' => false,
                'content' => 'Baru mulai nonton series ini, langsung ketagihan.',
                'created_at' => '3h ago',
                'likes' => 19,
                'is_liked' => false,
            ],
            [
                'id' => 4,
                'user' => [
                    'name' => 'Julian Croft',
                    'initials' => 'JC',
                    'badge' => 'PRO',
                    'is_verified' => false,
                ],
                'is_pinned' => false,
                'content' => 'Soundtrack-nya juara. Ada yang tahu judul lagunya?',
                'created_at' => '5h ago',
                'likes' => 24,
                'is_liked' => false,
            ],
            [
                'id' => 5,
                'user' => [
                    'name' => 'Devin Thorne',
                    'initials' => 'DT',
                    'badge' => 'FREE',
                    'is_verified' => false,
                ],
                'is_pinned' => false,
                'content' => 'Rekomendasi dong episode lain yang sekelas ini.',
                'created_at' => '8h ago',
                'likes' => 14,
                'is_liked' => false,
            ],
        ];

        return Inertia::render('films/Watch', [
            'film' => $filmData,
            'currentEpisode' => $currentEpisode,
            'episodes' => $episodes,
            'comments' => $comments,
        ]);
    }

    /**
     * Resolve film data by id with catalog matching.
     */
    private function resolveFilm(string|int $id): array
    {
        $films = [
            '101' => [
                'id' => 101,
                'title' => 'The Quiet Archipelago',
                'year' => '2024',
                'duration' => '1h 48m',
                'age_rating' => '18+',
                'rating' => '8.4',
                'category' => 'FILM',
                'genres' => ['Thriller', 'Drama', 'Mystery'],
                'subtitles' => 'ID · EN',
                'director' => 'Kaelen Ashford',
                'studio' => 'Watoon Originals',
                'creator' => [
                    'name' => 'Kaelen Ashford',
                    'initials' => 'KA',
                    'avatar' => null,
                ],
                'synopsis' => 'On a storm-swept northern coastline, a solitary sound archivist captures the disappearing echoes of a forgotten fishing community, uncovering a resonance that defies temporal boundaries.',
                'full_synopsis' => 'On a storm-swept northern coastline, a solitary sound archivist captures the disappearing echoes of a forgotten fishing community, uncovering a resonance that defies temporal boundaries. As the acoustic recordings deepen into forgotten coastal folklore, strange magnetic frequencies begin to align with celestial anomalies over the North Sea.',
                'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAbymq42E2as9jVxukwfG_XSzIeuTXeDPC53gRNTHhqbVSIXQLTwvFf6NE0qs8a5kO78jXHKfmEIRgw4JZZwIkzxPi-c_sPgltCJNOIXR_PCVyGl-O69ntD1Unq7MzV6JxdP7_ryPTuPsriznJ_EWYUO-_TJKbX38gPpGHoG_6tJRJw1Kl__HVKcmhmF3oylSyw9HUL5hEieT9F1oL5Qzk6ExxqfximmbzPE9xbwqZJxq3EMEyGpz44',
                'backdrop' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB2dzXB12HNvw1e9ezUXYpdNA0s5yLtyBahoSktTTbcSEtSeb0Xh92V63MsChwWyZoEzIP-Suo-62wg_kL03t1u9PyHMPTsx_j4lRFHTbLONSO1zXgqgjKSiCIrxozzNR0eeIZDalqogJ18c8Bf1EQj_jRWdlY3DXKbZvgSfDe-v5gDJRluN2BCKaxgdo_PRJfKwgdBeNg1hsh9DIO7SpolItM1_NB4C29rDJbYQ-KMJD-5cAzR-K3z',
            ],
            '102' => [
                'id' => 102,
                'title' => 'Northbound',
                'year' => '2024',
                'duration' => '2h 10m',
                'age_rating' => '16+',
                'rating' => '8.6',
                'category' => 'FILM',
                'genres' => ['Mystery', 'Nordic Noir', 'Thriller'],
                'subtitles' => 'ID · EN',
                'director' => 'Elin Lindqvist',
                'studio' => 'Watoon Originals',
                'creator' => [
                    'name' => 'Elin Lindqvist',
                    'initials' => 'EL',
                    'avatar' => null,
                ],
                'synopsis' => 'Penyelidikan mendalam di mercusuar terisolasi membuka jejak ekspedisi kutub utara yang diselimuti rahasia selama setengah abad.',
                'full_synopsis' => 'Penyelidikan mendalam di mercusuar terisolasi membuka jejak ekspedisi kutub utara yang diselimuti rahasia selama setengah abad. Saat salju lebat memutus jalur komunikasi dengan daratan utama, catatan ekspedisi terakhir mulai menyingkap kebenaran yang mengerikan.',
                'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAbSL11RV5rDrVo6iXm2ZPfqUYgKVMJbDhQdMV1LVABxr2zpd_ug-PpsPoQDbIEh59fZ8mTn92jiOAWhEmNZHwiiD-LkrqtaG2y86g842-1tHEiXp4aj8B3g-5rk7AKlUTOKOAhJOBkWjLs9mhrsd75VWlJozKKgAuby5xFiOft2rqnEC4rq-463Wn0mo2nbxsPsBgPbtzlHbVMC_ZnH0LnPZEG5WdXt1ReYXoCwL9q7ptapr2pfYSE',
                'backdrop' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB2dzXB12HNvw1e9ezUXYpdNA0s5yLtyBahoSktTTbcSEtSeb0Xh92V63MsChwWyZoEzIP-Suo-62wg_kL03t1u9PyHMPTsx_j4lRFHTbLONSO1zXgqgjKSiCIrxozzNR0eeIZDalqogJ18c8Bf1EQj_jRWdlY3DXKbZvgSfDe-v5gDJRluN2BCKaxgdo_PRJfKwgdBeNg1hsh9DIO7SpolItM1_NB4C29rDJbYQ-KMJD-5cAzR-K3z',
            ],
            '103' => [
                'id' => 103,
                'title' => 'The Archivist',
                'year' => '2023',
                'duration' => '1h 35m',
                'age_rating' => '18+',
                'rating' => '8.9',
                'category' => 'FILM',
                'genres' => ['Psychological', 'Thriller', 'Mystery'],
                'subtitles' => 'ID · EN',
                'director' => 'Marcus Vance',
                'studio' => 'Watoon Originals',
                'creator' => [
                    'name' => 'Marcus Vance',
                    'initials' => 'MV',
                    'avatar' => null,
                ],
                'synopsis' => 'Di ruang arsip tua yang temaram, penemuan pita rekaman analog tak berlabel mengubah persepsi seorang kurator tentang masa lalu kotanya.',
                'full_synopsis' => 'Di ruang arsip tua yang temaram, penemuan pita rekaman analog tak berlabel mengubah persepsi seorang kurator tentang masa lalu kotanya.',
                'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBmDcsm6JDKOKUnsBFeExouzdMUCoriToAp_36L1vMMbYHjFuG_TztU2C_M8XmIQnKeKwGUt9Hn7WNIrxLy8ISsTUqJf2kJCsMf8MAjP24wEaGqhJHVu-2C8xUvyqFCDrqrA_EjoNAxXUcQ2012XvBfzlGFwEh0nUb2hMBVJL3RxdCxMUoFQOqjclXGpPh5ZU4Q3_rqEkfmrmFC-nE3EZs18gIJUSr4FFi8dgXkBuwIoMlWoduS3Uj1',
                'backdrop' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB2dzXB12HNvw1e9ezUXYpdNA0s5yLtyBahoSktTTbcSEtSeb0Xh92V63MsChwWyZoEzIP-Suo-62wg_kL03t1u9PyHMPTsx_j4lRFHTbLONSO1zXgqgjKSiCIrxozzNR0eeIZDalqogJ18c8Bf1EQj_jRWdlY3DXKbZvgSfDe-v5gDJRluN2BCKaxgdo_PRJfKwgdBeNg1hsh9DIO7SpolItM1_NB4C29rDJbYQ-KMJD-5cAzR-K3z',
            ],
            '104' => [
                'id' => 104,
                'title' => 'Cold Meridian',
                'year' => '2024',
                'duration' => '2h 04m',
                'age_rating' => '16+',
                'rating' => '8.4',
                'category' => 'FILM',
                'genres' => ['Arctic Drama', 'Maritime', 'Atmospheric'],
                'subtitles' => 'ID · EN',
                'director' => 'Soren Kjeldsen',
                'studio' => 'Watoon Originals',
                'creator' => [
                    'name' => 'Soren Kjeldsen',
                    'initials' => 'SK',
                    'avatar' => null,
                ],
                'synopsis' => 'Pelayaran hening sebuah kapal kargo tua menembus labirin fjord beku saat musim dingin ekstrem menyelimuti samudra Atlantik Utara.',
                'full_synopsis' => 'Pelayaran hening sebuah kapal kargo tua menembus labirin fjord beku saat musim dingin ekstrem menyelimuti samudra Atlantik Utara.',
                'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuD7FguOrY_3FLyZ_pG2wOy9IIoeDVaTLvJwQkzuBtBZuheHhLEGoS_EPCFCzTnWaIqR2nC7J9XhRwJh8cM5lN0WZ-uBq9ym0VVMGdGjYGjE_ye9z2CaDeWNx-l9qHg8U-jbZoA6bS__Usv4wkAU6PJEscjQGNetjKgLC22vDA3AoNuNaHKE-K8O7Vgo1fcTjmaZR57BTmFmUSMyZYtY2rjz649ic0gPU0b0DEzzOqRC492hxEqQyUAn',
                'backdrop' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB2dzXB12HNvw1e9ezUXYpdNA0s5yLtyBahoSktTTbcSEtSeb0Xh92V63MsChwWyZoEzIP-Suo-62wg_kL03t1u9PyHMPTsx_j4lRFHTbLONSO1zXgqgjKSiCIrxozzNR0eeIZDalqogJ18c8Bf1EQj_jRWdlY3DXKbZvgSfDe-v5gDJRluN2BCKaxgdo_PRJfKwgdBeNg1hsh9DIO7SpolItM1_NB4C29rDJbYQ-KMJD-5cAzR-K3z',
            ],
            '105' => [
                'id' => 105,
                'title' => 'Night Train to Kyoto',
                'year' => '2024',
                'duration' => '1h 52m',
                'age_rating' => '13+',
                'rating' => '8.9',
                'category' => 'FILM',
                'genres' => ['Poetic Nocturne', 'Drama', 'Travel'],
                'subtitles' => 'ID · EN · JA',
                'director' => 'Kenjiro Mori',
                'studio' => 'Watoon Originals',
                'creator' => [
                    'name' => 'Kenjiro Mori',
                    'initials' => 'KM',
                    'avatar' => null,
                ],
                'synopsis' => 'Sebuah perjalanan malam melintasi hutan bambu berembun, mempertemukan dua musafir yang mencari kedamaian dalam keheningan gerbong kayu.',
                'full_synopsis' => 'Sebuah perjalanan malam melintasi hutan bambu berembun, mempertemukan dua musafir yang mencari kedamaian dalam keheningan gerbong kayu.',
                'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDbLvIilc6fs3YzLhFxAVZhxfnLD2DQHbCisa5RqZh1USWDuA3SbxXHosVJShvjSovpwopZNNbmyRLS3jmrvA5VOQQD9pEsRCV5XlArnubxcraf2yPmTp68daqitP9ue5hTGEetNoq7jrprnYGVlYP_c5koc79vjqxy_TEGwdYXV7twqEr51NKe0p8RvYjhnLvUp-MYUosHdV5BC_1vQEChGKEcheVL-fK4LJAL0xUgNpf0ggbYzY3d',
                'backdrop' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuB2dzXB12HNvw1e9ezUXYpdNA0s5yLtyBahoSktTTbcSEtSeb0Xh92V63MsChwWyZoEzIP-Suo-62wg_kL03t1u9PyHMPTsx_j4lRFHTbLONSO1zXgqgjKSiCIrxozzNR0eeIZDalqogJ18c8Bf1EQj_jRWdlY3DXKbZvgSfDe-v5gDJRluN2BCKaxgdo_PRJfKwgdBeNg1hsh9DIO7SpolItM1_NB4C29rDJbYQ-KMJD-5cAzR-K3z',
            ],
        ];

        $catalog = [
            '1' => ['title' => 'Solitude by Design', 'year' => '2024', 'genres' => ['Documentary', 'Art'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAPJZ3HaMzt88VuI8vveBpWUJoKNTWgqhdO9meVjJbOHb6zYz9cYXzl4K44CvvkxCc5gYDMM5m0aoRfIDvhXOUOSlaRTzanr1qwTqDM-am9HkiVvyKQfVQOU2cYu3uaf1c8X8h6uhzDTAifeg2SQfJft6K3IvDn1jRlha5bcJWMKF1hdRiSuqceW-PfM84956wFZS5RMBTVCRAidanh5P0lzHw1w4VAlRV8sOOO1pnWBjy-9ic8e-x3', 'rating' => '8.6'],
            '2' => ['title' => 'Dusk Over Manhattan', 'year' => '2023', 'genres' => ['Noir', 'Drama'], 'poster' => 'https://lh3.googleusercontent.com/aida/AEtjO1VxVGGw4aESkVj48zBpMA4gFCLbUCrpe13mskDfTdoWhN6WtSiVR532gzqKTnwl3EQYwqu33ldGR1Ao1C8jCWM2Q88eMDhzwrrfHwOswNAjOjsn-roS75cxKsvYhDqIMnfd_pj0Q40twJDMfqxB-BKYN92Db8gHtAwERoRXqEetpQcdQcMQlbib8RRQzzDfFt18T_TU6ygmbVjByR0qoIzdqEUcrf0b28G_4OCrBcNMk1GfoFC7ws1TWJc', 'rating' => '8.7'],
            '3' => ['title' => 'Chronicles of Silence', 'year' => '2024', 'genres' => ['Essay', 'Philosophy'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuARSgovhqArkqjbDk5vJuDMfAAHsEYgOpr7djEoM_gUKIOaYhfKE0rgwGkPUUL63q7xMObe_LMNKWtOV38Wfh5uA_C_mSLX7nkmElqrznApFQnEa6yfqgpvJ_Yr4seLqEQta9mIRPAJ1e16XK3Iv3GiyQgfS3ubRREUa7H9ufsrsVdOBozCnNRsT0SyNZ28Y1YF18PCk1k6KMYgKG-jdN0yQ7a3ZUqTo1Jr68UtAJ5wcpJAiRwAcgkR', 'rating' => '8.5'],
            '4' => ['title' => 'Aura of the Harbor', 'year' => '2024', 'genres' => ['Drama', 'Atmosphere'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuA75ZYduJ92CypXxVJaGQU7YQyhYVMC0U-C5la6fAj9-dtD_111KcZKKOaOR6bA4HPtQBTPhKBKz9JqRKikiSToG6SU_b6jsUWEqtIi2ED5XRylAevofgxHusXYJkde0tAD2K07Eq23TWtXQjXSBcvZtsB36R88Kh2DG_YuYutxsGZHcGXHgNlajtpHNmxFR9rChJc8vxtkQP2q65BnQbI7symvAkfaAyepu6G5ug5PH59yFlNHd7Xo', 'rating' => '8.8'],
            '5' => ['title' => 'Shadows in Monochrome', 'year' => '2024', 'genres' => ['Experimental', 'Visual'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDGPWBPNprLKpGImSEcCHWCweWLSqZZMg8PFtEVrdG63Jskj_u4ERvSC3HaVIyBmX-tUqXobxp7VDjMVy59rMcdxNRc1QSkfkMW57UloiYmwvtt4ta_ZGH26u9csjQbRxvSbd4H1fh8T8AeaYhrSSez_8jbWgbIUfQJfgPM6WyeZArbaDXT1ef5un5BvR3Z-X_mL2Kt2HCciBAjeg3ELADhvtbQS8HGp-TIcP3nhdJoVWkRuwhOdMfX', 'rating' => '8.9'],
            '6' => ['title' => 'Night Train to Kyoto', 'year' => '2024', 'genres' => ['Drama', 'Poetic Nocturne'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDbLvIilc6fs3YzLhFxAVZhxfnLD2DQHbCisa5RqZh1USWDuA3SbxXHosVJShvjSovpwopZNNbmyRLS3jmrvA5VOQQD9pEsRCV5XlArnubxcraf2yPmTp68daqitP9ue5hTGEetNoq7jrprnYGVlYP_c5koc79vjqxy_TEGwdYXV7twqEr51NKe0p8RvYjhnLvUp-MYUosHdV5BC_1vQEChGKEcheVL-fK4LJAL0xUgNpf0ggbYzY3d', 'rating' => '8.9'],
            '11' => ['title' => 'Northbound', 'year' => '2024', 'genres' => ['Mystery', 'Nordic Noir'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAbSL11RV5rDrVo6iXm2ZPfqUYgKVMJbDhQdMV1LVABxr2zpd_ug-PpsPoQDbIEh59fZ8mTn92jiOAWhEmNZHwiiD-LkrqtaG2y86g842-1tHEiXp4aj8B3g-5rk7AKlUTOKOAhJOBkWjLs9mhrsd75VWlJozKKgAuby5xFiOft2rqnEC4rq-463Wn0mo2nbxsPsBgPbtzlHbVMC_ZnH0LnPZEG5WdXt1ReYXoCwL9q7ptapr2pfYSE', 'rating' => '8.6'],
            '12' => ['title' => 'The Archivist', 'year' => '2023', 'genres' => ['Thriller', 'Psychological'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBmDcsm6JDKOKUnsBFeExouzdMUCoriToAp_36L1vMMbYHjFuG_TztU2C_M8XmIQnKeKwGUt9Hn7WNIrxLy8ISsTUqJf2kJCsMf8MAjP24wEaGqhJHVu-2C8xUvyqFCDrqrA_EjoNAxXUcQ2012XvBfzlGFwEh0nUb2hMBVJL3RxdCxMUoFQOqjclXGpPh5ZU4Q3_rqEkfmrmFC-nE3EZs18gIJUSr4FFi8dgXkBuwIoMlWoduS3Uj1', 'rating' => '8.9'],
            '13' => ['title' => 'Cold Meridian', 'year' => '2024', 'genres' => ['Drama', 'Maritime'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuD7FguOrY_3FLyZ_pG2wOy9IIoeDVaTLvJwQkzuBtBZuheHhLEGoS_EPCFCzTnWaIqR2nC7J9XhRwJh8cM5lN0WZ-uBq9ym0VVMGdGjYGjE_ye9z2CaDeWNx-l9qHg8U-jbZoA6bS__Usv4wkAU6PJEscjQGNetjKgLC22vDA3AoNuNaHKE-K8O7Vgo1fcTjmaZR57BTmFmUSMyZYtY2rjz649ic0gPU0b0DEzzOqRC492hxEqQyUAn', 'rating' => '8.4'],
            '14' => ['title' => 'Subterranea', 'year' => '2024', 'genres' => ['Sci-Fi', 'Mystery'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCK02uLiaIUq9aP6T17yCtg7S-ZWcLHkM8q9HQGuvSFK8HLgGjsFJbkw7EhxlafDPzOlegiUHPa2nlYDyHNJjFWXTiIltPxSBVFqjv589cyHBxYrqJ3dyPovoq7u78pSEEYfseCf1_EqdIiwUDry5C1R-RpEdPjObBQrxj8bVfft55X5VtJHzTGFJtIuV_WRGcmXj2kDwbcYGHTIec57_vGjfd70jNa-vsMODg83VktfxT7hGYErXPM', 'rating' => '8.7'],
            '15' => ['title' => 'Acoustic Memory', 'year' => '2023', 'genres' => ['Essay', 'Sound'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCMHIUh3zc9II4xww7PeJKUI8NlqPzgER-Ker9xV_9ckFtAE2o5j5n9VQmXLckW-XaDZHhH7JfJvHIspP3xTNNFHud57lk-mCQZGumbVhXP2FmnLvPDzu13M5_tkWMDnqbhWQ2XFdbIGGnfqZQuPKPwmh1A_zN5VpkNpkjqK-pIqIBwrNDInkSMTChEafcl0BsrF9Ju5y8-00k4_acMZRsuTIQ4mFo0B8fS1a13rUYRE_lY9_BzdTgk', 'rating' => '8.9'],
            '16' => ['title' => 'The Outer Ridge', 'year' => '2024', 'genres' => ['Nature', 'Atmosphere'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDe5cJcqxuNQiSBLHImbEUL9_c8ZUURA41XG-4fby-w7-cd0gaAeylqSyh4amHYiuR0A9-PvRRMJRVkTrw3C_f9phDVXEiZsMS1CgBNNN6uyhOlnE2K25h-qvByd_D_BSY6BLajf3qnnAwZ8s9vXFM-0PfyrJk2cEAwMzklVq_ZrMNnbfGINVUn7PnfI4CBDXjBEGxujeayey4u_5_z6udiULKHNVEs1pP849pHxoRtVM0CLvPq5sY0', 'rating' => '8.5'],
            '21' => ['title' => 'The Kiln House', 'year' => '2024', 'genres' => ['Documentary', 'Craft'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCPKYPZVwearNOSrhohmQ2dzIIhmNEgrO5sN7m0QMm-oKQNfg9ffDjRssPFrHe6l7Ug-swsA9NMN67Op9NLDLsKZewVxBFXv1UU8zqnA_0LfkZFZPUatk6fPcjMDol2_mU_ms-WJM1TcnC5EmGLpAf2G2I1TRoFaEPAEx4z-fL732NeFt7efM7E9nBJbutFuJaGslLR49WVeaK2nFjkjNgZwa97Y8nHRMvu9vC1jGh6wnEAspK79Q8C', 'rating' => '8.8'],
            '22' => ['title' => 'Frequency 14', 'year' => '2024', 'genres' => ['Experimental', 'Sci-Fi'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBaX2ewnx-8Awym8fdkWJk1l-mlqx6wv2Y6ZPhXoTrrYuiEFO_6r-AsXOC4RI49ZJItG80-H7Zt4fmwbd5ROvdKgzAg1xEgWvbb0MrODBDh8dHHCmsuigdnYO-RXZBu4ablvDW09hXPz-ZdIhgQ8CUDtp5wYawH6hnO0vsdPOF0DRfq4dHIlrcIpkiKY9lK6uAP7Ilt4RHpJxbb-o15SmC31pfq6U7gYIiucOfxcQuUDpSy3NQvfbTZ', 'rating' => '8.5'],
            '23' => ['title' => 'Tide & Tarmac', 'year' => '2024', 'genres' => ['Narrative', 'Drama'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCXvkGNQtkq7yQBlB6LHwmWNahw_8iJbNZr_keJ8eapPi42f_ZFAgiTPCoIJxA9oND8EsxmFOgGKNKdXVoklni89gwHPlajiJngbegnd8j9sXq0kyRnuVHWkv_QjQeWVSmcjXcw9h296EqKo1Y39uPJ_TYFlHb5ZTvbYASr6IthYyYIBenwC0W1Rcx5DZ-qp485iugO0O5kz-yF_NFtvXesyVmjhEULLiRbemgjBxbw5HIiz_xe925H', 'rating' => '8.7'],
            '24' => ['title' => 'The Paper Master', 'year' => '2024', 'genres' => ['Short Film', 'Art'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDx4NC7_GxE6n55258yBMEQj6rQc4B_9rgJ4dx32kUmrHezOAsQAyZ5pMPl-FIR2vJLtrVr5A7Kr36MmnvmKJ_-XWJTgv-Eo_F67x72X8FA3TV2SzA9ju_1UTvaBz4Q_VeKMawvvTdLdbL5RFo2XsxWGlSAaJdsH32vQqs3T9ACnloYjNGbsMCXTZh1lyoxV907oDyxM3hybDlDsl_bAG0MGPt9fAjAoU-jSF4NEehvDNd-9NmJLUfK', 'rating' => '9.0'],
            '25' => ['title' => 'Linear Forms', 'year' => '2024', 'genres' => ['Essay', 'Architecture'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDGPWBPNprLKpGImSEcCHWCweWLSqZZMg8PFtEVrdG63Jskj_u4ERvSC3HaVIyBmX-tUqXobxp7VDjMVy59rMcdxNRc1QSkfkMW57UloiYmwvtt4ta_ZGH26u9csjQbRxvSbd4H1fh8T8AeaYhrSSez_8jbWgbIUfQJfgPM6WyeZArbaDXT1ef5un5BvR3Z-X_mL2Kt2HCciBAjeg3ELADhvtbQS8HGp-TIcP3nhdJoVWkRuwhOdMfX', 'rating' => '8.6'],
            '31' => ['title' => 'Echoes of the North', 'year' => '2024', 'genres' => ['Mystery', 'Drama'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAbSL11RV5rDrVo6iXm2ZPfqUYgKVMJbDhQdMV1LVABxr2zpd_ug-PpsPoQDbIEh59fZ8mTn92jiOAWhEmNZHwiiD-LkrqtaG2y86g842-1tHEiXp4aj8B3g-5rk7AKlUTOKOAhJOBkWjLs9mhrsd75VWlJozKKgAuby5xFiOft2rqnEC4rq-463Wn0mo2nbxsPsBgPbtzlHbVMC_ZnH0LnPZEG5WdXt1ReYXoCwL9q7ptapr2pfYSE', 'rating' => '8.7'],
            '32' => ['title' => 'The Cartographer', 'year' => '2023', 'genres' => ['Thriller', 'Exploration'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBmDcsm6JDKOKUnsBFeExouzdMUCoriToAp_36L1vMMbYHjFuG_TztU2C_M8XmIQnKeKwGUt9Hn7WNIrxLy8ISsTUqJf2kJCsMf8MAjP24wEaGqhJHVu-2C8xUvyqFCDrqrA_EjoNAxXUcQ2012XvBfzlGFwEh0nUb2hMBVJL3RxdCxMUoFQOqjclXGpPh5ZU4Q3_rqEkfmrmFC-nE3EZs18gIJUSr4FFi8dgXkBuwIoMlWoduS3Uj1', 'rating' => '8.8'],
            '33' => ['title' => 'Polar Passage', 'year' => '2024', 'genres' => ['Drama', 'Expedition'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuD7FguOrY_3FLyZ_pG2wOy9IIoeDVaTLvJwQkzuBtBZuheHhLEGoS_EPCFCzTnWaIqR2nC7J9XhRwJh8cM5lN0WZ-uBq9ym0VVMGdGjYGjE_ye9z2CaDeWNx-l9qHg8U-jbZoA6bS__Usv4wkAU6PJEscjQGNetjKgLC22vDA3AoNuNaHKE-K8O7Vgo1fcTjmaZR57BTmFmUSMyZYtY2rjz649ic0gPU0b0DEzzOqRC492hxEqQyUAn', 'rating' => '8.4'],
            '34' => ['title' => 'Station Zero', 'year' => '2024', 'genres' => ['Sci-Fi', 'Isolation'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCK02uLiaIUq9aP6T17yCtg7S-ZWcLHkM8q9HQGuvSFK8HLgGjsFJbkw7EhxlafDPzOlegiUHPa2nlYDyHNJjFWXTiIltPxSBVFqjv589cyHBxYrqJ3dyPovoq7u78pSEEYfseCf1_EqdIiwUDry5C1R-RpEdPjObBQrxj8bVfft55X5VtJHzTGFJtIuV_WRGcmXj2kDwbcYGHTIec57_vGjfd70jNa-vsMODg83VktfxT7hGYErXPM', 'rating' => '8.7'],
            '35' => ['title' => 'Magnetic Resonance', 'year' => '2023', 'genres' => ['Essay', 'Sound'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCMHIUh3zc9II4xww7PeJKUI8NlqPzgER-Ker9xV_9ckFtAE2o5j5n9VQmXLckW-XaDZHhH7JfJvHIspP3xTNNFHud57lk-mCQZGumbVhXP2FmnLvPDzu13M5_tkWMDnqbhWQ2XFdbIGGnfqZQuPKPwmh1A_zN5VpkNpkjqK-pIqIBwrNDInkSMTChEafcl0BsrF9Ju5y8-00k4_acMZRsuTIQ4mFo0B8fS1a13rUYRE_lY9_BzdTgk', 'rating' => '8.9'],
            '36' => ['title' => 'Basalt Plain', 'year' => '2024', 'genres' => ['Nature', 'Atmospheric'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDe5cJcqxuNQiSBLHImbEUL9_c8ZUURA41XG-4fby-w7-cd0gaAeylqSyh4amHYiuR0A9-PvRRMJRVkTrw3C_f9phDVXEiZsMS1CgBNNN6uyhOlnE2K25h-qvByd_D_BSY6BLajf3qnnAwZ8s9vXFM-0PfyrJk2cEAwMzklVq_ZrMNnbfGINVUn7PnfI4CBDXjBEGxujeayey4u_5_z6udiULKHNVEs1pP849pHxoRtVM0CLvPq5sY0', 'rating' => '8.5'],
            '37' => ['title' => 'Fired Earth', 'year' => '2023', 'genres' => ['Craft', 'Art'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCPKYPZVwearNOSrhohmQ2dzIIhmNEgrO5sN7m0QMm-oKQNfg9ffDjRssPFrHe6l7Ug-swsA9NMN67Op9NLDLsKZewVxBFXv1UU8zqnA_0LfkZFZPUatk6fPcjMDol2_mU_ms-WJM1TcnC5EmGLpAf2G2I1TRoFaEPAEx4z-fL732NeFt7efM7E9nBJbutFuJaGslLR49WVeaK2nFjkjNgZwa97Y8nHRMvu9vC1jGh6wnEAspK79Q8C', 'rating' => '8.6'],
            '38' => ['title' => 'Open Air Recorders', 'year' => '2024', 'genres' => ['Experimental', 'Soundscape'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBaX2ewnx-8Awym8fdkWJk1l-mlqx6wv2Y6ZPhXoTrrYuiEFO_6r-AsXOC4RI49ZJItG80-H7Zt4fmwbd5ROvdKgzAg1xEgWvbb0MrODBDh8dHHCmsuigdnYO-RXZBu4ablvDW09hXPz-ZdIhgQ8CUDtp5wYawH6hnO0vsdPOF0DRfq4dHIlrcIpkiKY9lK6uAP7Ilt4RHpJxbb-o15SmC31pfq6U7gYIiucOfxcQuUDpSy3NQvfbTZ', 'rating' => '8.5'],
            '39' => ['title' => 'Nocturne by Water', 'year' => '2024', 'genres' => ['Narrative', 'Drama'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCXvkGNQtkq7yQBlB6LHwmWNahw_8iJbNZr_keJ8eapPi42f_ZFAgiTPCoIJxA9oND8EsxmFOgGKNKdXVoklni89gwHPlajiJngbegnd8j9sXq0kyRnuVHWkv_QjQeWVSmcjXcw9h296EqKo1Y39uPJ_TYFlHb5ZTvbYASr6IthYyYIBenwC0W1Rcx5DZ-qp485iugO0O5kz-yF_NFtvXesyVmjhEULLiRbemgjBxbw5HIiz_xe925H', 'rating' => '8.7'],
            '40' => ['title' => 'Folio & Spine', 'year' => '2023', 'genres' => ['Short Film', 'Archive'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDx4NC7_GxE6n55258yBMEQj6rQc4B_9rgJ4dx32kUmrHezOAsQAyZ5pMPl-FIR2vJLtrVr5A7Kr36MmnvmKJ_-XWJTgv-Eo_F67x72X8FA3TV2SzA9ju_1UTvaBz4Q_VeKMawvvTdLdbL5RFo2XsxWGlSAaJdsH32vQqs3T9ACnloYjNGbsMCXTZh1lyoxV907oDyxM3hybDlDsl_bAG0MGPt9fAjAoU-jSF4NEehvDNd-9NmJLUfK', 'rating' => '9.0'],
            '41' => ['title' => 'Diagonal Geometries', 'year' => '2024', 'genres' => ['Essay', 'Design'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDGPWBPNprLKpGImSEcCHWCweWLSqZZMg8PFtEVrdG63Jskj_u4ERvSC3HaVIyBmX-tUqXobxp7VDjMVy59rMcdxNRc1QSkfkMW57UloiYmwvtt4ta_ZGH26u9csjQbRxvSbd4H1fh8T8AeaYhrSSez_8jbWgbIUfQJfgPM6WyeZArbaDXT1ef5un5BvR3Z-X_mL2Kt2HCciBAjeg3ELADhvtbQS8HGp-TIcP3nhdJoVWkRuwhOdMfX', 'rating' => '8.6'],
            '42' => ['title' => 'Through the Grove', 'year' => '2024', 'genres' => ['Drama', 'Atmosphere'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDbLvIilc6fs3YzLhFxAVZhxfnLD2DQHbCisa5RqZh1USWDuA3SbxXHosVJShvjSovpwopZNNbmyRLS3jmrvA5VOQQD9pEsRCV5XlArnubxcraf2yPmTp68daqitP9ue5hTGEetNoq7jrprnYGVlYP_c5koc79vjqxy_TEGwdYXV7twqEr51NKe0p8RvYjhnLvUp-MYUosHdV5BC_1vQEChGKEcheVL-fK4LJAL0xUgNpf0ggbYzY3d', 'rating' => '8.8'],
            '43' => ['title' => 'The Cloister', 'year' => '2023', 'genres' => ['Philosophy', 'Contemplation'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuARSgovhqArkqjbDk5vJuDMfAAHsEYgOpr7djEoM_gUKIOaYhfKE0rgwGkPUUL63q7xMObe_LMNKWtOV38Wfh5uA_C_mSLX7nkmElqrznApFQnEa6yfqgpvJ_Yr4seLqEQta9mIRPAJ1e16XK3Iv3GiyQgfS3ubRREUa7H9ufsrsVdOBozCnNRsT0SyNZ28Y1YF18PCk1k6KMYgKG-jdN0yQ7a3ZUqTo1Jr68UtAJ5wcpJAiRwAcgkR', 'rating' => '8.6'],
            '44' => ['title' => 'Terminal Sleep', 'year' => '2024', 'genres' => ['Atmosphere', 'Noir'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuA75ZYduJ92CypXxVJaGQU7YQyhYVMC0U-C5la6fAj9-dtD_111KcZKKOaOR6bA4HPtQBTPhKBKz9JqRKikiSToG6SU_b6jsUWEqtIi2ED5XRylAevofgxHusXYJkde0tAD2K07Eq23TWtXQjXSBcvZtsB36R88Kh2DG_YuYutxsGZHcGXHgNlajtpHNmxFR9rChJc8vxtkQP2q65BnQbI7symvAkfaAyepu6G5ug5PH59yFlNHd7Xo', 'rating' => '8.7'],
            '45' => ['title' => 'Glass & Asphalt', 'year' => '2023', 'genres' => ['Noir', 'Urban Drama'], 'poster' => 'https://lh3.googleusercontent.com/aida/AEtjO1VxVGGw4aESkVj48zBpMA4gFCLbUCrpe13mskDfTdoWhN6WtSiVR532gzqKTnwl3EQYwqu33ldGR1Ao1C8jCWM2Q88eMDhzwrrfHwOswNAjOjsn-roS75cxKsvYhDqIMnfd_pj0Q40twJDMfqxB-BKYN92Db8gHtAwERoRXqEetpQcdQcMQlbib8RRQzzDfFt18T_TU6ygmbVjByR0qoIzdqEUcrf0b28G_4OCrBcNMk1GfoFC7ws1TWJc', 'rating' => '8.5'],
            '46' => ['title' => 'Fjord Horizon', 'year' => '2024', 'genres' => ['Nordic Drama', 'Landscape'], 'poster' => 'https://lh3.googleusercontent.com/aida/AEtjO1Uf-TV0jxjqj9Jb_iu9txy1sg5YpoTM-WHj6bq7raYvchbiywBMky2dtzxr2d34I84FyoA7cp9w4lOryYb5nxAZBCbKTkhn6Wk_nccBVx_CPbsqzQUKuaR5H5gJIj-R5GVt6NqxwXrboY--n8jNHNiF-2-CGuxe87plA5f2Tq3FpQnwQqn5716T1sz3oxIsG-WRmSKVaJ4nslCjyui06H0O675pQV4GikPjjKfJLrVwvdn9fMIxhis_tx4', 'rating' => '8.9'],
            '47' => ['title' => 'Quiet Hours', 'year' => '2024', 'genres' => ['Study', 'Documentary'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAPJZ3HaMzt88VuI8vveBpWUJoKNTWgqhdO9meVjJbOHb6zYz9cYXzl4K44CvvkxCc5gYDMM5m0aoRfIDvhXOUOSlaRTzanr1qwTqDM-am9HkiVvyKQfVQOU2cYu3uaf1c8X8h6uhzDTAifeg2SQfJft6K3IvDn1jRlha5bcJWMKF1hdRiSuqceW-PfM84956wFZS5RMBTVCRAidanh5P0lzHw1w4VAlRV8sOOO1pnWBjy-9ic8e-x3', 'rating' => '8.4'],
            '48' => ['title' => 'Signals in the Fog', 'year' => '2023', 'genres' => ['Mystery', 'Investigation'], 'poster' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuAbSL11RV5rDrVo6iXm2ZPfqUYgKVMJbDhQdMV1LVABxr2zpd_ug-PpsPoQDbIEh59fZ8mTn92jiOAWhEmNZHwiiD-LkrqtaG2y86g842-1tHEiXp4aj8B3g-5rk7AKlUTOKOAhJOBkWjLs9mhrsd75VWlJozKKgAuby5xFiOft2rqnEC4rq-463Wn0mo2nbxsPsBgPbtzlHbVMC_ZnH0LnPZEG5WdXt1ReYXoCwL9q7ptapr2pfYSE', 'rating' => '8.6'],
        ];

        $key = (string) $id;
        $film = $films[$key] ?? $films['101'];

        if (isset($catalog[$key])) {
            $cat = $catalog[$key];
            $film['id'] = (int) $id;
            $film['title'] = $cat['title'];
            $film['year'] = $cat['year'];
            $film['poster'] = $cat['poster'];
            $film['genres'] = $cat['genres'];
            $film['rating'] = $cat['rating'];
        } elseif (! isset($films[$key])) {
            $film['id'] = (int) $id;
        }

        return $film;
    }

    /**
     * Get episodes collection.
     */
    private function getEpisodesData(): array
    {
        return [
            [
                'number' => '08',
                'code' => 'EP 08',
                'title' => 'Echoes of the Fjord',
                'synopsis' => 'An unexpected sonic disturbance at dawn prompts an offshore expedition into the uncharted sound corridors. As radio anomalies compound, the research crew detects historical transmissions that shouldn\'t exist.',
                'badge' => 'VIP',
                'duration' => '48m',
                'thumbnail' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDKAGQ_Dj3k2rmw4GSuK9sODN0fyz9PDxca5jMFSWls1Th8RiLzn2qDGdi3Knf1ll7kS4N_RNDg9aOMWavSmiG_GNiCPlf1O0upQsdz00qBJAfTeDc9Znb4-8JW8jH1iT4x3e5j52MOb_32J6swoDOAEEan7j6fDoi-7698hY48tZ9le-4dMBABZpoiiqGnDYaFKPu3hfNEtCLnxX7C8QqnCPXoewARNGbG_x0oXEEyfa9xDOuE-Urn',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/BigBuckBunny.mp4',
            ],
            [
                'number' => '07',
                'code' => 'EP 07',
                'title' => 'The Salt Archives',
                'synopsis' => 'Hidden magnetic spools reveal recorded conversations from the winter of 1974.',
                'badge' => 'VIP',
                'duration' => '44m',
                'thumbnail' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuCzenn1rL1AGOGaB2kp7-Zz_0Mk7ymDRtixZLJVvwTFj_aaE2vB9MPp-TQUFmxkrDA8IdMpusHuKB5-cpvCbdK3Bs6KrQE7aKDqVqiw0-lUS_-zUHtxerAACRCAGvqPIObksFct1mhDX87Qpt8bv2xzeAuxqoF8o4gLEJ527vU04rmMj_Q1vdCW_3aGqoz4uAdyCpFD_HInX5Bpp5a4eJcUT01EOQlIoZolUwQtleZ5a_2qXHG4Wl8V',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ElephantsDream.mp4',
            ],
            [
                'number' => '06',
                'code' => 'EP 06',
                'title' => 'Frequency of Solitude',
                'synopsis' => 'Isolation tests mental clarity as ambient sounds begin to reconstruct forgotten dialogues.',
                'badge' => 'PRO',
                'duration' => '52m',
                'thumbnail' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBZMZpgdqF94XleSja2abHsJYWvrTvI5_NqB1dt_hXSkS9UBxtPEJXQxNqwlh8irFtnPfkklapCSBzm-DCvOY5fQGdXtjGjSjnjhaNtyQR1WdgRzR2bmA7OKVjwnbmcKAW0wck4TnUm94O5Lf7ncxtNxXmBPVv0TauPE1bbYFDHhRn5B8JQpNjyh61UpayodpXKewGLC91SdVdeM0oJFRs3iiIv0ZcGvvH9h5QewxxjWZClqAojEmWT',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerBlazes.mp4',
            ],
            [
                'number' => '05',
                'code' => 'EP 05',
                'title' => 'Submerged Signals',
                'synopsis' => 'Hydrophone cables dropped beneath the sea ice detect rhythmic acoustic pulses.',
                'badge' => 'PRO',
                'duration' => '41m',
                'thumbnail' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuArW3r8HCpOcRVgw12aq5AzcJ7guoMkvLVK5_nsuJTvXAPt-90JUt1pEeJrm11pu55C6O__oGlpcvXW-seDm47YLqxqZLfOcOyuFpsJeYQsuowu7n--IV9I7n7tBtICyJ2yUl010kgkfRHUfW9VIOUqEjXuC_eTFrNkzuBPgdALH6aSzwzbgQuKkgkrDli2kmgF-wzrTh2yuOe6i1XB0kGhZO1UaHB_v_WRQaC1uS4gy3wTGN2SBoxU',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerEscapes.mp4',
            ],
            [
                'number' => '04',
                'code' => 'EP 04',
                'title' => "The Lantern Keeper's Log",
                'synopsis' => 'A missing century-old handwritten logbook connects previous lighthouse attendants to current anomalies.',
                'badge' => 'PRO',
                'duration' => '46m',
                'thumbnail' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBTdLykpSBIKPd5d679c56vdr8_6CeckFASKncDCfZKAe8EenkPy0le1NE1CmXlnmMFkuonfBEQNFqlS-biphWRm3bRHOD5p4tXnBugH3L9AyHcmr9gVgZYoMPMIADLlco3_8TZ7590Zi2lAVWFu7gs8PLW0KjLZR2CKEQDq7biEtsZ9rpg7ebCr_QJyIB4v3pjLTYUhaNLHYmuQFS3b40p5xnjn5SyR5m-iPXGyQu9qfyMJNj1d8VZ',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerFun.mp4',
            ],
            [
                'number' => '03',
                'code' => 'EP 03',
                'title' => 'Barometric Drift',
                'synopsis' => 'Atmospheric shifts trigger simultaneous resonance across all radio receiver frequencies.',
                'badge' => 'PRO',
                'duration' => '50m',
                'thumbnail' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDAqnFClMJMWOnGM2r5fdv3lMsr7YkO8i5udw-iqAGPhX3rIwy3V8UC5z0t1Uo_EQBUyFbMnDbM_tsI9X3siKuGxhWMzCUeefERZEs5Rk4WUej3KNr06I5_MkLXPjW0pOaATqGxZnMvAwylIwBx9FWBIhJIdkLqfWTe3N5BGr_5UO-8V6BkHr48TkjKB2RUQIE90MyCGOxqVX_KGhpZnDA-hqzTjW_NXh2c8vD3v55HHTC0aN5QKe-B',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerJoyBlazes.mp4',
            ],
            [
                'number' => '02',
                'code' => 'EP 02',
                'title' => 'Tidal Resonance',
                'synopsis' => 'Nighttime fog conditions amplify low-frequency maritime broadcasts along the cape.',
                'badge' => 'FREE',
                'duration' => '47m',
                'thumbnail' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuDKAGQ_Dj3k2rmw4GSuK9sODN0fyz9PDxca5jMFSWls1Th8RiLzn2qDGdi3Knf1ll7kS4N_RNDg9aOMWavSmiG_GNiCPlf1O0upQsdz00qBJAfTeDc9Znb4-8JW8jH1iT4x3e5j52MOb_32J6swoDOAEEan7j6fDoi-7698hY48tZ9le-4dMBABZpoiiqGnDYaFKPu3hfNEtCLnxX7C8QqnCPXoewARNGbG_x0oXEEyfa9xDOuE-Urn',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/ForBiggerMeltdowns.mp4',
            ],
            [
                'number' => '01',
                'code' => 'EP 01',
                'title' => 'Threshold of Silence',
                'synopsis' => 'Kaelen Ashford establishes the acoustic listening post on the outer archipelago rim.',
                'badge' => 'FREE',
                'duration' => '55m',
                'thumbnail' => 'https://lh3.googleusercontent.com/aida-public/AB6AXuBZMZpgdqF94XleSja2abHsJYWvrTvI5_NqB1dt_hXSkS9UBxtPEJXQxNqwlh8irFtnPfkklapCSBzm-DCvOY5fQGdXtjGjSjnjhaNtyQR1WdgRzR2bmA7OKVjwnbmcKAW0wck4TnUm94O5Lf7ncxtNxXmBPVv0TauPE1bbYFDHhRn5B8JQpNjyh61UpayodpXKewGLC91SdVdeM0oJFRs3iiIv0ZcGvvH9h5QewxxjWZClqAojEmWT',
                'video_url' => 'https://commondatastorage.googleapis.com/gtv-videos-bucket/sample/Sintel.mp4',
            ],
        ];
    }
}
