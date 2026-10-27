import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import AppLayout from '@/layouts/app-layout';
import { type BreadcrumbItem } from '@/types';
import { Head, Link } from '@inertiajs/react';
import { ArrowDown, ArrowUpRight, ClipboardList, Plus } from 'lucide-react';

type FoodOrder = {
    id: number;
    dispatch_id: string;
    dispatch_status: string;
    total_bill: string | number;
    created_at: string;
    customer: { customer_name: string };
    restaurant: { restaurant_name: string };
    rider: { rider_name: string } | null;
    items: { id: number; menu_item_name: string; quantity: number }[];
};

type PaginationLink = { url: string | null; label: string; active: boolean };
type FoodOrderPage = {
    data: FoodOrder[];
    links: PaginationLink[];
    current_page: number;
    last_page: number;
    from: number | null;
    to: number | null;
    total: number;
};

const breadcrumbs: BreadcrumbItem[] = [{ title: 'Food orders', href: route('food-orders.index') }];

const currency = new Intl.NumberFormat('en-PH', { style: 'currency', currency: 'PHP' });
const paginationLabel = (label: string) =>
    label.replace(/<[^>]*>/g, '').replaceAll('&laquo;', '‹').replaceAll('&raquo;', '›');

function statusVariant(status: string): 'default' | 'secondary' | 'destructive' | 'outline' {
    if (status === 'delivered') return 'default';
    if (status === 'cancelled') return 'destructive';
    if (status === 'pending') return 'outline';
    return 'secondary';
}

export default function FoodOrdersIndex({ foodOrders, success }: { foodOrders: FoodOrderPage; success?: string }) {
    return (
        <AppLayout breadcrumbs={breadcrumbs}>
            <Head title="Food orders" />

            <main className="mx-auto w-full max-w-7xl space-y-8 p-4 md:p-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                    <div>
                        <p className="text-sm font-medium text-primary">Order management</p>
                        <h1 className="mt-1 text-3xl font-semibold tracking-tight">Food orders</h1>
                        <p className="mt-2 text-muted-foreground">Track customer orders and their delivery progress.</p>
                    </div>
                    <Button asChild>
                        <Link href={route('food-orders.create')}>
                            <Plus />
                            Create order
                        </Link>
                    </Button>
                </div>

                {success && (
                    <div role="status" className="rounded-lg border border-emerald-500/30 bg-emerald-500/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                        {success}
                    </div>
                )}

                <div className="grid gap-4 sm:grid-cols-3">
                    <Card>
                        <CardContent className="flex items-center gap-4 pt-6">
                            <span className="flex size-11 items-center justify-center rounded-lg bg-primary/10 text-primary"><ClipboardList className="size-5" /></span>
                            <div>
                                <p className="text-sm text-muted-foreground">Total orders</p>
                                <p className="text-2xl font-semibold">{foodOrders.total}</p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-4 pt-6">
                            <span className="flex size-11 items-center justify-center rounded-lg bg-amber-500/10 text-amber-600"><ArrowUpRight className="size-5" /></span>
                            <div>
                                <p className="text-sm text-muted-foreground">Current page</p>
                                <p className="text-2xl font-semibold">{foodOrders.current_page} <span className="text-base font-normal text-muted-foreground">of {foodOrders.last_page}</span></p>
                            </div>
                        </CardContent>
                    </Card>
                    <Card>
                        <CardContent className="flex items-center gap-4 pt-6">
                            <span className="flex size-11 items-center justify-center rounded-lg bg-blue-500/10 text-blue-600"><ArrowDown className="size-5" /></span>
                            <div>
                                <p className="text-sm text-muted-foreground">Showing</p>
                                <p className="text-2xl font-semibold">{foodOrders.from ?? 0}–{foodOrders.to ?? 0}</p>
                            </div>
                        </CardContent>
                    </Card>
                </div>

                <Card>
                    <CardHeader>
                        <CardTitle className="text-lg">Recent orders</CardTitle>
                        <CardDescription>Order details, items, and dispatch status.</CardDescription>
                    </CardHeader>
                    <CardContent>
                        {foodOrders.data.length === 0 ? (
                            <div className="flex flex-col items-center rounded-lg border border-dashed px-6 py-16 text-center">
                                <span className="flex size-12 items-center justify-center rounded-full bg-muted"><ClipboardList className="size-6 text-muted-foreground" /></span>
                                <h2 className="mt-4 font-medium">No food orders yet</h2>
                                <p className="mt-1 max-w-sm text-sm text-muted-foreground">Create your first order to start tracking deliveries.</p>
                                <Button asChild className="mt-5">
                                    <Link href={route('food-orders.create')}><Plus /> Create order</Link>
                                </Button>
                            </div>
                        ) : (
                            <div className="overflow-x-auto">
                                <table className="w-full min-w-[850px] text-left text-sm">
                                    <thead>
                                        <tr className="border-b text-xs font-medium uppercase tracking-wide text-muted-foreground">
                                            <th className="px-3 py-3">Dispatch</th>
                                            <th className="px-3 py-3">Customer</th>
                                            <th className="px-3 py-3">Restaurant & items</th>
                                            <th className="px-3 py-3">Rider</th>
                                            <th className="px-3 py-3">Total</th>
                                            <th className="px-3 py-3">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        {foodOrders.data.map((order) => (
                                            <tr key={order.id} className="border-b last:border-0">
                                                <td className="px-3 py-4">
                                                    <p className="font-medium">{order.dispatch_id}</p>
                                                    <p className="mt-1 text-xs text-muted-foreground">{new Date(order.created_at).toLocaleDateString()}</p>
                                                </td>
                                                <td className="px-3 py-4">{order.customer.customer_name}</td>
                                                <td className="px-3 py-4">
                                                    <p className="font-medium">{order.restaurant.restaurant_name}</p>
                                                    <p className="mt-1 max-w-sm truncate text-xs text-muted-foreground">
                                                        {order.items.map((item) => `${item.quantity}× ${item.menu_item_name}`).join(', ')}
                                                    </p>
                                                </td>
                                                <td className="px-3 py-4">{order.rider?.rider_name ?? 'Unassigned'}</td>
                                                <td className="px-3 py-4 font-medium">{currency.format(Number(order.total_bill))}</td>
                                                <td className="px-3 py-4">
                                                    <Badge variant={statusVariant(order.dispatch_status)}>
                                                        {order.dispatch_status.replaceAll('_', ' ')}
                                                    </Badge>
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}

                        {foodOrders.last_page > 1 && (
                            <nav aria-label="Pagination" className="mt-6 flex flex-wrap justify-end gap-2 border-t pt-4">
                                {foodOrders.links.map((link, index) => (
                                    link.url ? (
                                        <Button key={index} asChild variant={link.active ? 'default' : 'outline'} size="sm">
                                            <Link href={link.url}>{paginationLabel(link.label)}</Link>
                                        </Button>
                                    ) : (
                                        <Button key={index} variant="outline" size="sm" disabled>
                                            <span>{paginationLabel(link.label)}</span>
                                        </Button>
                                    )
                                ))}
                            </nav>
                        )}
                    </CardContent>
                </Card>
            </main>
        </AppLayout>
    );
}
