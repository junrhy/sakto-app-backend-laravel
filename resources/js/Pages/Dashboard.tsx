import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { format } from "date-fns";

interface Props {
    stats: {
        totalClients: number;
        activeClients: number;
        totalMessages: number;
        unreadMessages: number;
        totalClientDetails: number;
        pendingCreditRequests: number;
        totalCredits: number;
    };
    overview: {
        date: string;
        total_requests: number;
        approved_credits: number;
        pending_credits: number;
    }[];
    recentSales: {
        id: number;
        client_name: string;
        client_identifier: string;
        package_credit: number;
        status: string;
        created_at: string;
    }[];
}

export default function Dashboard({ stats, overview, recentSales }: Props) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="flex-col md:flex">
                <div className="flex-1 space-y-4 p-8 pt-6">
                    <div className="flex items-center justify-between space-y-2">
                        <h2 className="text-3xl font-bold tracking-tight">Dashboard</h2>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-4">
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Total Clients
                                </CardTitle>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    className="h-4 w-4 text-muted-foreground"
                                >
                                    <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2" />
                                    <circle cx="9" cy="7" r="4" />
                                    <path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75" />
                                </svg>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.totalClients}</div>
                                <p className="text-xs text-muted-foreground">
                                    {stats.activeClients} active clients
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Messages
                                </CardTitle>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    className="h-4 w-4 text-muted-foreground"
                                >
                                    <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z" />
                                </svg>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.totalMessages}</div>
                                <p className="text-xs text-muted-foreground">
                                    {stats.unreadMessages} unread messages
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Client Details
                                </CardTitle>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    className="h-4 w-4 text-muted-foreground"
                                >
                                    <path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6" />
                                </svg>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.totalClientDetails}</div>
                                <p className="text-xs text-muted-foreground">
                                    Total client configurations
                                </p>
                            </CardContent>
                        </Card>
                        <Card>
                            <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2">
                                <CardTitle className="text-sm font-medium">
                                    Credit Requests
                                </CardTitle>
                                <svg
                                    xmlns="http://www.w3.org/2000/svg"
                                    viewBox="0 0 24 24"
                                    fill="none"
                                    stroke="currentColor"
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="2"
                                    className="h-4 w-4 text-muted-foreground"
                                >
                                    <path d="M22 12h-4l-3 9L9 3l-3 9H2" />
                                </svg>
                            </CardHeader>
                            <CardContent>
                                <div className="text-2xl font-bold">{stats.pendingCreditRequests}</div>
                                <p className="text-xs text-muted-foreground">
                                    {stats.totalCredits} total credits
                                </p>
                            </CardContent>
                        </Card>
                    </div>
                    <div className="grid gap-4 md:grid-cols-2 lg:grid-cols-7">
                        <Card className="col-span-4">
                            <CardHeader>
                                <CardTitle>Credit Overview</CardTitle>
                                <CardDescription>
                                    Credit requests and approvals for the last 7 days
                                </CardDescription>
                            </CardHeader>
                            <CardContent className="pl-2">
                                <div className="space-y-4">
                                    {overview.map((item) => (
                                        <div key={item.date} className="flex items-center justify-between">
                                            <div className="space-y-1">
                                                <p className="text-sm font-medium leading-none">
                                                    {format(new Date(item.date), 'MMM dd, yyyy')}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {item.total_requests} requests
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-4">
                                                <div className="text-sm text-green-600">
                                                    +{item.approved_credits} approved
                                                </div>
                                                <div className="text-sm text-yellow-600">
                                                    {item.pending_credits} pending
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                        <Card className="col-span-3">
                            <CardHeader>
                                <CardTitle>Recent Credit Requests</CardTitle>
                                <CardDescription>
                                    Latest credit requests from clients
                                </CardDescription>
                            </CardHeader>
                            <CardContent>
                                <div className="space-y-4">
                                    {recentSales.map((sale) => (
                                        <div key={sale.id} className="flex items-center justify-between">
                                            <div className="space-y-1">
                                                <p className="text-sm font-medium leading-none">
                                                    {sale.client_name}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {sale.client_identifier}
                                                </p>
                                            </div>
                                            <div className="flex items-center gap-4">
                                                <div className="text-sm font-medium">
                                                    {sale.package_credit} credits
                                                </div>
                                                <div className={`text-sm ${
                                                    sale.status === 'approved' ? 'text-green-600' :
                                                    sale.status === 'pending' ? 'text-yellow-600' :
                                                    'text-red-600'
                                                }`}>
                                                    {sale.status}
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>
                            </CardContent>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
