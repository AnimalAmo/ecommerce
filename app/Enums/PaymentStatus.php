<?php

namespace App\Enums;

/**
 * Stato del pagamento (OrderPayment). Value inglesi lowercase; la transizione
 * a Completed è quella osservata per l'evento OrderPaid (fase pipeline).
 */
enum PaymentStatus: string
{
    case Pending = 'pending';
    case Processing = 'processing';
    case Completed = 'completed';
    case Failed = 'failed';
    case Cancelled = 'cancelled';
    case Refunded = 'refunded';
}
