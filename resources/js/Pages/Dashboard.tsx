import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { Head } from '@inertiajs/react';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from "@/Components/ui/card";
import { format } from "date-fns";
import { TrendingUp, Users, MessageSquare, FileText, CreditCard, Activity, Calendar, CheckCircle, Clock, XCircle, BarChart3, PieChart, LineChart as LucideLineChart } from 'lucide-react';
import { LineChart, Line, AreaChart, Area, BarChart, Bar, PieChart as RechartsPieChart, Pie, Cell, XAxis, YAxis, CartesianGrid, Tooltip, ResponsiveContainer, Legend } from 'recharts';

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
    const formatNumber = (num: number) => {
        return new Intl.NumberFormat().format(num);
    };

    const getStatusIcon = (status: string) => {
        switch (status) {
            case 'approved':
                return <CheckCircle className="h-4 w-4 text-green-500" />;
            case 'pending':
                return <Clock className="h-4 w-4 text-yellow-500" />;
            default:
                return <XCircle className="h-4 w-4 text-red-500" />;
        }
    };

    const getStatusColor = (status: string) => {
        switch (status) {
            case 'approved':
                return 'bg-green-100 text-green-800 border-green-200';
            case 'pending':
                return 'bg-yellow-100 text-yellow-800 border-yellow-200';
            default:
                return 'bg-red-100 text-red-800 border-red-200';
        }
    };

    // Prepare data for charts
    const chartData = overview.map(item => ({
        date: format(new Date(item.date), 'MMM dd'),
        approved: item.approved_credits,
        pending: item.pending_credits,
        total: item.total_requests,
    }));

    const clientDistributionData = [
        { name: 'Active Clients', value: stats.activeClients, color: '#10b981' },
        { name: 'Inactive Clients', value: stats.totalClients - stats.activeClients, color: '#6b7280' },
    ];

    const creditStatusData = recentSales.reduce((acc, sale) => {
        acc[sale.status] = (acc[sale.status] || 0) + 1;
        return acc;
    }, {} as Record<string, number>);

    const creditStatusChartData = Object.entries(creditStatusData).map(([status, count]) => ({
        name: status.charAt(0).toUpperCase() + status.slice(1),
        value: count,
        color: status === 'approved' ? '#10b981' : status === 'pending' ? '#f59e0b' : '#ef4444',
    }));

    const COLORS = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6'];

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="min-h-screen bg-gradient-to-br from-slate-50 via-blue-50 to-indigo-50">
                <div className="flex-col md:flex">
                    <div className="flex-1 space-y-6 p-8 pt-6">
                        {/* Header Section */}
                        <div className="flex items-center justify-between">
                            <div>
                                <h1 className="text-4xl font-bold tracking-tight bg-gradient-to-r from-gray-900 via-blue-800 to-indigo-900 bg-clip-text text-transparent">
                                    Dashboard
                                </h1>
                                <p className="text-gray-600 mt-2">Welcome back! Here's what's happening with your business.</p>
                            </div>
                            <div className="hidden md:flex items-center space-x-2 bg-white/80 backdrop-blur-sm rounded-lg px-4 py-2 shadow-sm border">
                                <Calendar className="h-5 w-5 text-blue-600" />
                                <span className="text-sm font-medium text-gray-700">
                                    {format(new Date(), 'EEEE, MMMM do, yyyy')}
                                </span>
                            </div>
                        </div>

                        {/* Stats Cards */}
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                            <Card className="group hover:shadow-lg transition-all duration-300 border-0 bg-gradient-to-br from-blue-500 to-blue-600 text-white overflow-hidden relative">
                                <div className="absolute inset-0 bg-gradient-to-r from-blue-600/20 to-transparent"></div>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 relative z-10">
                                    <CardTitle className="text-sm font-medium text-blue-100">
                                        Total Clients
                                    </CardTitle>
                                    <div className="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                                        <Users className="h-5 w-5 text-white" />
                                    </div>
                                </CardHeader>
                                <CardContent className="relative z-10">
                                    <div className="text-3xl font-bold">{formatNumber(stats.totalClients)}</div>
                                    <p className="text-sm text-blue-100 flex items-center gap-1 mt-1">
                                        <TrendingUp className="h-3 w-3" />
                                        {formatNumber(stats.activeClients)} active clients
                                    </p>
                                </CardContent>
                            </Card>

                            <Card className="group hover:shadow-lg transition-all duration-300 border-0 bg-gradient-to-br from-emerald-500 to-emerald-600 text-white overflow-hidden relative">
                                <div className="absolute inset-0 bg-gradient-to-r from-emerald-600/20 to-transparent"></div>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 relative z-10">
                                    <CardTitle className="text-sm font-medium text-emerald-100">
                                        Messages
                                    </CardTitle>
                                    <div className="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                                        <MessageSquare className="h-5 w-5 text-white" />
                                    </div>
                                </CardHeader>
                                <CardContent className="relative z-10">
                                    <div className="text-3xl font-bold">{formatNumber(stats.totalMessages)}</div>
                                    <p className="text-sm text-emerald-100 flex items-center gap-1 mt-1">
                                        <Activity className="h-3 w-3" />
                                        {formatNumber(stats.unreadMessages)} unread
                                    </p>
                                </CardContent>
                            </Card>

                            <Card className="group hover:shadow-lg transition-all duration-300 border-0 bg-gradient-to-br from-purple-500 to-purple-600 text-white overflow-hidden relative">
                                <div className="absolute inset-0 bg-gradient-to-r from-purple-600/20 to-transparent"></div>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 relative z-10">
                                    <CardTitle className="text-sm font-medium text-purple-100">
                                        Client Details
                                    </CardTitle>
                                    <div className="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                                        <FileText className="h-5 w-5 text-white" />
                                    </div>
                                </CardHeader>
                                <CardContent className="relative z-10">
                                    <div className="text-3xl font-bold">{formatNumber(stats.totalClientDetails)}</div>
                                    <p className="text-sm text-purple-100 flex items-center gap-1 mt-1">
                                        <CheckCircle className="h-3 w-3" />
                                        Total configurations
                                    </p>
                                </CardContent>
                            </Card>

                            <Card className="group hover:shadow-lg transition-all duration-300 border-0 bg-gradient-to-br from-orange-500 to-orange-600 text-white overflow-hidden relative">
                                <div className="absolute inset-0 bg-gradient-to-r from-orange-600/20 to-transparent"></div>
                                <CardHeader className="flex flex-row items-center justify-between space-y-0 pb-2 relative z-10">
                                    <CardTitle className="text-sm font-medium text-orange-100">
                                        Credit Requests
                                    </CardTitle>
                                    <div className="p-2 bg-white/20 rounded-lg backdrop-blur-sm">
                                        <CreditCard className="h-5 w-5 text-white" />
                                    </div>
                                </CardHeader>
                                <CardContent className="relative z-10">
                                    <div className="text-3xl font-bold">{formatNumber(stats.pendingCreditRequests)}</div>
                                    <p className="text-sm text-orange-100 flex items-center gap-1 mt-1">
                                        <TrendingUp className="h-3 w-3" />
                                        {formatNumber(stats.totalCredits)} total credits
                                    </p>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Charts Section */}
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
                            {/* Credit Trends Line Chart */}
                            <Card className="lg:col-span-2 group hover:shadow-lg transition-all duration-300 border-0 bg-white/80 backdrop-blur-sm">
                                <CardHeader className="border-b border-gray-100">
                                    <CardTitle className="flex items-center gap-2 text-xl font-semibold text-gray-800">
                                        <LucideLineChart className="h-5 w-5 text-blue-600" />
                                        Credit Requests Trend
                                    </CardTitle>
                                    <CardDescription className="text-gray-600">
                                        Daily credit requests and approvals over the last 7 days
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-6">
                                    <div className="h-80">
                                        <ResponsiveContainer width="100%" height="100%">
                                            <LineChart data={chartData}>
                                                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                                <XAxis 
                                                    dataKey="date" 
                                                    stroke="#6b7280"
                                                    fontSize={12}
                                                />
                                                <YAxis 
                                                    stroke="#6b7280"
                                                    fontSize={12}
                                                />
                                                <Tooltip 
                                                    contentStyle={{
                                                        backgroundColor: 'white',
                                                        border: '1px solid #e5e7eb',
                                                        borderRadius: '8px',
                                                        boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                                                    }}
                                                />
                                                <Legend />
                                                <Line 
                                                    type="monotone" 
                                                    dataKey="approved" 
                                                    stroke="#10b981" 
                                                    strokeWidth={3}
                                                    dot={{ fill: '#10b981', strokeWidth: 2, r: 4 }}
                                                    activeDot={{ r: 6 }}
                                                />
                                                <Line 
                                                    type="monotone" 
                                                    dataKey="pending" 
                                                    stroke="#f59e0b" 
                                                    strokeWidth={3}
                                                    dot={{ fill: '#f59e0b', strokeWidth: 2, r: 4 }}
                                                    activeDot={{ r: 6 }}
                                                />
                                            </LineChart>
                                        </ResponsiveContainer>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Client Distribution Pie Chart */}
                            <Card className="group hover:shadow-lg transition-all duration-300 border-0 bg-white/80 backdrop-blur-sm">
                                <CardHeader className="border-b border-gray-100">
                                    <CardTitle className="flex items-center gap-2 text-xl font-semibold text-gray-800">
                                        <PieChart className="h-5 w-5 text-emerald-600" />
                                        Client Distribution
                                    </CardTitle>
                                    <CardDescription className="text-gray-600">
                                        Active vs inactive clients
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-6">
                                    <div className="h-80">
                                        <ResponsiveContainer width="100%" height="100%">
                                            <RechartsPieChart>
                                                <Pie
                                                    data={clientDistributionData}
                                                    cx="50%"
                                                    cy="50%"
                                                    innerRadius={60}
                                                    outerRadius={100}
                                                    paddingAngle={5}
                                                    dataKey="value"
                                                >
                                                    {clientDistributionData.map((entry, index) => (
                                                        <Cell key={`cell-${index}`} fill={entry.color} />
                                                    ))}
                                                </Pie>
                                                <Tooltip 
                                                    contentStyle={{
                                                        backgroundColor: 'white',
                                                        border: '1px solid #e5e7eb',
                                                        borderRadius: '8px',
                                                        boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                                                    }}
                                                />
                                                <Legend />
                                            </RechartsPieChart>
                                        </ResponsiveContainer>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Additional Charts Section */}
                        <div className="grid gap-6 md:grid-cols-2">
                            {/* Credit Status Distribution */}
                            <Card className="group hover:shadow-lg transition-all duration-300 border-0 bg-white/80 backdrop-blur-sm">
                                <CardHeader className="border-b border-gray-100">
                                    <CardTitle className="flex items-center gap-2 text-xl font-semibold text-gray-800">
                                        <BarChart3 className="h-5 w-5 text-purple-600" />
                                        Credit Status Distribution
                                    </CardTitle>
                                    <CardDescription className="text-gray-600">
                                        Breakdown of recent credit request statuses
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-6">
                                    <div className="h-80">
                                        <ResponsiveContainer width="100%" height="100%">
                                            <BarChart data={creditStatusChartData}>
                                                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                                <XAxis 
                                                    dataKey="name" 
                                                    stroke="#6b7280"
                                                    fontSize={12}
                                                />
                                                <YAxis 
                                                    stroke="#6b7280"
                                                    fontSize={12}
                                                />
                                                <Tooltip 
                                                    contentStyle={{
                                                        backgroundColor: 'white',
                                                        border: '1px solid #e5e7eb',
                                                        borderRadius: '8px',
                                                        boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                                                    }}
                                                />
                                                <Bar dataKey="value" fill="#8b5cf6" radius={[4, 4, 0, 0]} />
                                            </BarChart>
                                        </ResponsiveContainer>
                                    </div>
                                </CardContent>
                            </Card>

                            {/* Total Requests Area Chart */}
                            <Card className="group hover:shadow-lg transition-all duration-300 border-0 bg-white/80 backdrop-blur-sm">
                                <CardHeader className="border-b border-gray-100">
                                    <CardTitle className="flex items-center gap-2 text-xl font-semibold text-gray-800">
                                        <AreaChart className="h-5 w-5 text-orange-600" />
                                        Total Requests Volume
                                    </CardTitle>
                                    <CardDescription className="text-gray-600">
                                        Daily total request volume trend
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-6">
                                    <div className="h-80">
                                        <ResponsiveContainer width="100%" height="100%">
                                            <AreaChart data={chartData}>
                                                <CartesianGrid strokeDasharray="3 3" stroke="#e5e7eb" />
                                                <XAxis 
                                                    dataKey="date" 
                                                    stroke="#6b7280"
                                                    fontSize={12}
                                                />
                                                <YAxis 
                                                    stroke="#6b7280"
                                                    fontSize={12}
                                                />
                                                <Tooltip 
                                                    contentStyle={{
                                                        backgroundColor: 'white',
                                                        border: '1px solid #e5e7eb',
                                                        borderRadius: '8px',
                                                        boxShadow: '0 4px 6px -1px rgba(0, 0, 0, 0.1)',
                                                    }}
                                                />
                                                <Area 
                                                    type="monotone" 
                                                    dataKey="total" 
                                                    stroke="#f97316" 
                                                    fill="url(#colorGradient)"
                                                    strokeWidth={2}
                                                />
                                                <defs>
                                                    <linearGradient id="colorGradient" x1="0" y1="0" x2="0" y2="1">
                                                        <stop offset="5%" stopColor="#f97316" stopOpacity={0.8}/>
                                                        <stop offset="95%" stopColor="#f97316" stopOpacity={0.1}/>
                                                    </linearGradient>
                                                </defs>
                                            </AreaChart>
                                        </ResponsiveContainer>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        {/* Tables Section */}
                        <div className="grid gap-6 md:grid-cols-2 lg:grid-cols-7">
                            <Card className="col-span-4 group hover:shadow-lg transition-all duration-300 border-0 bg-white/80 backdrop-blur-sm">
                                <CardHeader className="border-b border-gray-100">
                                    <CardTitle className="flex items-center gap-2 text-xl font-semibold text-gray-800">
                                        <Activity className="h-5 w-5 text-blue-600" />
                                        Credit Overview
                                    </CardTitle>
                                    <CardDescription className="text-gray-600">
                                        Credit requests and approvals for the last 7 days
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-6">
                                    <div className="space-y-4">
                                        {overview.map((item, index) => (
                                            <div key={item.date} className="group/item flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition-colors duration-200">
                                                <div className="space-y-1">
                                                    <p className="text-sm font-semibold leading-none text-gray-900">
                                                        {format(new Date(item.date), 'MMM dd, yyyy')}
                                                    </p>
                                                    <p className="text-sm text-gray-500">
                                                        {formatNumber(item.total_requests)} requests
                                                    </p>
                                                </div>
                                                <div className="flex items-center gap-4">
                                                    <div className="flex items-center gap-2 text-sm font-medium text-green-600 bg-green-50 px-3 py-1 rounded-full">
                                                        <CheckCircle className="h-3 w-3" />
                                                        +{formatNumber(item.approved_credits)} approved
                                                    </div>
                                                    <div className="flex items-center gap-2 text-sm font-medium text-yellow-600 bg-yellow-50 px-3 py-1 rounded-full">
                                                        <Clock className="h-3 w-3" />
                                                        {formatNumber(item.pending_credits)} pending
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </CardContent>
                            </Card>

                            <Card className="col-span-3 group hover:shadow-lg transition-all duration-300 border-0 bg-white/80 backdrop-blur-sm">
                                <CardHeader className="border-b border-gray-100">
                                    <CardTitle className="flex items-center gap-2 text-xl font-semibold text-gray-800">
                                        <CreditCard className="h-5 w-5 text-purple-600" />
                                        Recent Credit Requests
                                    </CardTitle>
                                    <CardDescription className="text-gray-600">
                                        Latest credit requests from clients
                                    </CardDescription>
                                </CardHeader>
                                <CardContent className="p-6">
                                    <div className="space-y-4">
                                        {recentSales.map((sale) => (
                                            <div key={sale.id} className="group/item flex items-center justify-between p-4 rounded-lg hover:bg-gray-50 transition-colors duration-200 border border-gray-100">
                                                <div className="space-y-1">
                                                    <p className="text-sm font-semibold leading-none text-gray-900">
                                                        {sale.client_name}
                                                    </p>
                                                    <p className="text-sm text-gray-500 font-mono">
                                                        {sale.client_identifier}
                                                    </p>
                                                </div>
                                                <div className="flex flex-col items-end gap-2">
                                                    <div className="text-sm font-bold text-gray-900 bg-gray-100 px-2 py-1 rounded">
                                                        {formatNumber(sale.package_credit)} credits
                                                    </div>
                                                    <div className={`flex items-center gap-1 text-xs font-medium px-2 py-1 rounded-full border ${getStatusColor(sale.status)}`}>
                                                        {getStatusIcon(sale.status)}
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
            </div>
        </AuthenticatedLayout>
    );
}
