<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Event\Event;
use App\Models\Order\Order;
use App\Models\SmartboxPackage\SmartboxPackage;
use App\Models\Structure\Structure;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

/**
 * Ordini demo di Giulia Rossi per il profilo "I miei ordini" (mock XD):
 *  - 1 ordine in programma con 3 articoli e totale 476,00 € (Hotel Brescia 215,
 *    Weekend in Piemonte 143, Weekend di escursioni 118 — righe del carrello XD),
 *    foto/titoli dal catalogo reale, finestre prenotate future;
 *  - 1 ordine regalo smartbox con dedica/messaggio (riepilogo flusso gift);
 *  - 2 ordini passati (tab Passati + bottone "Scrivi una recensione").
 *
 * Idempotente: cancella e ricrea i soli ordini dell'utente demo (order_number
 * sempre dal generator ORD-xxxxxx, mai fisso). WithoutModelEvents: il payment
 * Completed non deve emettere OrderPaid (mail reali) durante il seeding — per
 * questo order_number va passato esplicito (il creating hook è muto).
 */
class DemoOrderSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        $giulia = User::where('email', 'giulia.rossi@gmail.com')->firstOrFail();

        // Idempotenza: si ricreano i soli demo dell'utente demo (cascade FK su items/payment).
        Order::where('user_id', $giulia->id)->delete();

        $this->seedUpcomingOrder($giulia);
        $this->seedGiftOrder($giulia);
        $this->seedPastOrders($giulia);
    }

    /** Ordine "in programma" del mock: 3 articoli, totale 476,00 €. */
    private function seedUpcomingOrder(User $giulia): void
    {
        $hotel = Structure::where('slug', 'hotel-brescia')->orderBy('position')->firstOrFail();
        $box = SmartboxPackage::where('slug', 'piemonte')->firstOrFail();
        $excursion = Event::where('slug', 'weekend-escursioni')->firstOrFail();

        // Finestra futura (bucket In programma): 5 notti come il range del mock.
        $checkIn = CarbonImmutable::today()->addDays(31);
        $checkOut = $checkIn->addDays(5);

        $this->createOrder($giulia, CarbonImmutable::parse('2024-01-17 10:00'), false, [
            [
                'purchasable_type' => 'structure',
                'purchasable_id' => $hotel->id,
                'title' => $hotel->name,
                'photo_url' => asset('img/xd/'.$hotel->img.'.jpg'),
                'product_type' => $hotel->type,
                'location' => $hotel->location,
                'price_cents' => 21500,
                'is_gift' => false,
                'options' => [
                    'animals' => ['cane' => 1],
                    'check_in' => $checkIn->toDateString(),
                    'check_out' => $checkOut->toDateString(),
                    'guests' => ['adulti' => 2, 'bambini' => 0, 'ragazzi' => 0],
                ],
                'booked_from' => $checkIn,
                'booked_until' => $checkOut,
            ],
            [
                'purchasable_type' => 'smartbox_package',
                'purchasable_id' => $box->id,
                'title' => $box->title,
                'photo_url' => asset('img/xd/'.$box->img.'.jpg'),
                'product_type' => $box->type,
                // La card mock del riepilogo mostra 'Torino, Italia' (non l'audience del cofanetto).
                'location' => 'Torino, Italia',
                'price_cents' => 14300,
                'is_gift' => false,
                'options' => [
                    'animals' => ['cane' => 1],
                    'guests' => ['adulti' => 2, 'bambini' => 0, 'ragazzi' => 0],
                ],
                'booked_from' => CarbonImmutable::now(),
                'booked_until' => CarbonImmutable::now()->addMonths($box->validity_months),
            ],
            [
                'purchasable_type' => 'event',
                'purchasable_id' => $excursion->id,
                'title' => $excursion->title,
                'photo_url' => asset('img/xd/'.$excursion->img.'.jpg'),
                'product_type' => $excursion->type,
                'location' => $excursion->location,
                'price_cents' => 11800,
                'is_gift' => false,
                'options' => ['animals' => ['cane' => 1], 'participants' => 2],
                'booked_from' => $checkIn,
                'booked_until' => $checkOut,
            ],
        ]);
    }

    /** Ordine regalo: smartbox con dedica/messaggio nelle options.gift (riepilogo flusso gift). */
    private function seedGiftOrder(User $giulia): void
    {
        $box = SmartboxPackage::where('slug', 'relax-lombardia-2')->firstOrFail();

        $this->createOrder($giulia, CarbonImmutable::parse('2024-02-05 16:30'), true, [
            [
                'purchasable_type' => 'smartbox_package',
                'purchasable_id' => $box->id,
                'title' => $box->title,
                'photo_url' => asset('img/xd/'.$box->img.'.jpg'),
                'product_type' => $box->type,
                'location' => $box->audience,
                'price_cents' => $box->price_cents,
                'is_gift' => true,
                'options' => [
                    'animals' => ['cane' => 1],
                    'gift' => [
                        'dedication' => 'Per Marco',
                        'message' => 'Tanti auguri! Goditi un weekend di relax insieme a Fido.',
                        'recipient_email' => 'marco.bianchi@example.com',
                    ],
                ],
                'booked_from' => CarbonImmutable::now(),
                'booked_until' => CarbonImmutable::now()->addMonths($box->validity_months),
            ],
        ]);
    }

    /** Due ordini con finestre concluse: tab Passati + bottone recensione (mock: 22/06/2023, 2 articoli, 345 €). */
    private function seedPastOrders(User $giulia): void
    {
        $hotel = Structure::where('slug', 'hotel-mantova-residence')->orderBy('position')->firstOrFail();
        $puppyYogaMilano = Event::where('slug', 'puppy-yoga-milano')->firstOrFail();
        $puppyYoga = Event::where('slug', 'puppy-yoga')->firstOrFail();

        $this->createOrder($giulia, CarbonImmutable::parse('2023-06-22 09:15'), false, [
            [
                'purchasable_type' => 'structure',
                'purchasable_id' => $hotel->id,
                'title' => $hotel->name,
                'photo_url' => asset('img/xd/'.$hotel->img.'.jpg'),
                'product_type' => $hotel->type,
                'location' => $hotel->location,
                'price_cents' => 32000,
                'is_gift' => false,
                'options' => [
                    'animals' => ['cane' => 1],
                    'check_in' => '2023-06-25',
                    'check_out' => '2023-06-30',
                    'guests' => ['adulti' => 2, 'bambini' => 0, 'ragazzi' => 0],
                ],
                'booked_from' => CarbonImmutable::parse('2023-06-25'),
                'booked_until' => CarbonImmutable::parse('2023-06-30'),
            ],
            [
                'purchasable_type' => 'event',
                'purchasable_id' => $puppyYogaMilano->id,
                'title' => $puppyYogaMilano->title,
                'photo_url' => asset('img/xd/'.$puppyYogaMilano->img.'.jpg'),
                'product_type' => $puppyYogaMilano->type,
                'location' => $puppyYogaMilano->location,
                'price_cents' => 2500,
                'is_gift' => false,
                'options' => ['animals' => ['cane' => 1], 'participants' => 2],
                'booked_from' => CarbonImmutable::parse('2023-07-01 15:30'),
                'booked_until' => CarbonImmutable::parse('2023-07-01 17:30'),
            ],
        ]);

        $this->createOrder($giulia, CarbonImmutable::parse('2025-03-10 18:40'), false, [
            [
                'purchasable_type' => 'event',
                'purchasable_id' => $puppyYoga->id,
                'title' => $puppyYoga->title,
                'photo_url' => asset('img/xd/'.$puppyYoga->img.'.jpg'),
                'product_type' => $puppyYoga->type,
                'location' => $puppyYoga->location,
                'price_cents' => $puppyYoga->price_cents,
                'is_gift' => false,
                'options' => ['animals' => ['cane' => 1], 'participants' => 1],
                'booked_from' => $puppyYoga->starts_at,
                'booked_until' => $puppyYoga->ends_at,
            ],
        ]);
    }

    /**
     * Ordine Paid + righe snapshot + payment Completed (nessun evento: seeding
     * muto). Totale = somma righe; created_at forzato post-create (la data
     * della riga lista è quella d'acquisto del mock).
     *
     * @param  list<array<string, mixed>>  $items
     */
    private function createOrder(User $user, CarbonImmutable $createdAt, bool $isGift, array $items): void
    {
        $order = Order::create([
            'order_number' => Order::generateOrderNumber(),
            'user_id' => $user->id,
            'status' => OrderStatus::Paid,
            'is_gift' => $isGift,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'email' => $user->email,
            'phone' => $user->phone,
            'country' => 'Italia',
            'total_cents' => (int) array_sum(array_column($items, 'price_cents')),
        ]);

        foreach ($items as $item) {
            $order->items()->create($item);
        }

        $order->payment()->create([
            'payment_method' => PaymentMethod::Card,
            'status' => PaymentStatus::Completed,
            'amount_cents' => $order->total_cents,
            'transaction_id' => 'pi_demo_'.$order->id,
            'gateway_session_id' => 'pi_demo_'.$order->id,
            'provider' => 'stripe',
            'provider_response' => ['status' => 'succeeded'],
            'paid_at' => $createdAt,
        ]);

        $order->forceFill(['created_at' => $createdAt, 'updated_at' => $createdAt])->save();
    }
}
