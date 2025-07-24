import { Head } from '@inertiajs/react';
import { PageProps } from '@/types';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useState } from 'react';
import { router } from '@inertiajs/react';

interface CreditHistory {
    id: number;
    client_identifier: string;
    client: {
        name: string;
    } | null;
    package_name: string;
    package_credit: number;
    package_amount: number;
    payment_method: string;
    payment_method_details: string | null;
    transaction_id: string | null;
    proof_of_payment: string | null;
    status: string;
    approved_date: string | null;
    approved_by: string | null;
    created_at: string;
}

interface ClientWithCredits {
    id: number;
    name: string;
    client_identifier: string;
    email: string;
    contact_number: string | null;
    active: boolean;
    current_credits: number;
    pending_credits: number;
    total_purchased: number;
    total_spent: number;
    last_activity: string | null;
    recent_history: Array<{
        type: 'purchase' | 'spent';
        amount: number;
        status: string;
        date: string;
        details: string;
    }>;
}

interface Props extends PageProps {
    creditRequests: {
        data: CreditHistory[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
    clientsWithCredits: ClientWithCredits[];
}

export default function Index({ auth, creditRequests, clientsWithCredits }: Props) {
    const [processing, setProcessing] = useState<number | null>(null);
    const [activeTab, setActiveTab] = useState<'requests' | 'clients'>('requests');
    const [selectedClient, setSelectedClient] = useState<ClientWithCredits | null>(null);
    const [showHistoryModal, setShowHistoryModal] = useState(false);
    const [currentPage, setCurrentPage] = useState(1);
    const [itemsPerPage] = useState(5);

    const handleAction = async (id: number, action: 'accept' | 'reject') => {
        try {
            setProcessing(id);
            router.post(`/credits/${action}-request/${id}`, {}, {
                onSuccess: () => {
                    setProcessing(null);
                },
                onError: () => {
                    setProcessing(null);
                }
            });
        } catch (error) {
            console.error('Error processing request:', error);
            setProcessing(null);
        }
    };

    const handleViewHistory = (client: ClientWithCredits) => {
        setSelectedClient(client);
        setShowHistoryModal(true);
        setCurrentPage(1); // Reset to first page when opening modal
    };

    const closeHistoryModal = () => {
        setShowHistoryModal(false);
        setSelectedClient(null);
        setCurrentPage(1);
    };

    // Pagination logic for history
    const getPaginatedHistory = () => {
        if (!selectedClient) {
            return { items: [], totalPages: 0, currentPage: 1, totalItems: 0 };
        }
        
        // Ensure recent_history is an array
        const history = Array.isArray(selectedClient.recent_history) 
            ? selectedClient.recent_history 
            : [];
        
        const startIndex = (currentPage - 1) * itemsPerPage;
        const endIndex = startIndex + itemsPerPage;
        const items = history.slice(startIndex, endIndex);
        const totalPages = Math.ceil(history.length / itemsPerPage);
        
        return { items, totalPages, currentPage, totalItems: history.length };
    };

    const { items: paginatedHistory, totalPages, totalItems } = getPaginatedHistory();

    const goToPage = (page: number) => {
        setCurrentPage(page);
    };

    const goToPreviousPage = () => {
        if (currentPage > 1) {
            setCurrentPage(currentPage - 1);
        }
    };

    const goToNextPage = () => {
        if (currentPage < totalPages) {
            setCurrentPage(currentPage + 1);
        }
    };

    const formatDate = (dateString: string) => {
        return new Date(dateString).toLocaleString('en-US', {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            hour: '2-digit',
            minute: '2-digit'
        });
    };

    const formatAmount = (amount: number): string => {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP'
        }).format(amount);
    };

    const formatCredits = (credits: number): string => {
        return credits.toLocaleString();
    };

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Manage Credit Requests</h2>}
        >
            <Head title="Credit Requests" />

            <div className="py-12">
                <div className="w-full mx-auto px-4 sm:px-6 lg:px-8">
                    {/* Tab Navigation */}
                    <div className="mb-6">
                        <nav className="flex space-x-8">
                            <button
                                onClick={() => setActiveTab('requests')}
                                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                                    activeTab === 'requests'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                }`}
                            >
                                Credit Requests ({creditRequests.total})
                            </button>
                            <button
                                onClick={() => setActiveTab('clients')}
                                className={`py-2 px-1 border-b-2 font-medium text-sm ${
                                    activeTab === 'clients'
                                        ? 'border-indigo-500 text-indigo-600'
                                        : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'
                                }`}
                            >
                                Client Credits ({clientsWithCredits.length})
                            </button>
                        </nav>
                    </div>

                    {/* Credit Requests Tab */}
                    {activeTab === 'requests' && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Pending Credit Requests</h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Client Name
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Package Details
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Payment Info
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Status
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Request Date
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {creditRequests.data.map((request) => (
                                                <tr key={request.id}>
                                                    <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-900">
                                                        {request.client?.name || (
                                                            <div>
                                                                <div>Unknown Client</div>
                                                                <div className="text-xs text-gray-500">ID: {request.client_identifier}</div>
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-4 whitespace-nowrap">
                                                        <div className="text-sm text-gray-900 font-medium">
                                                            {request.package_name}
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {request.package_credit.toLocaleString()} Credits
                                                        </div>
                                                        <div className="text-sm text-gray-500">
                                                            {formatAmount(request.package_amount)}
                                                        </div>
                                                    </td>
                                                    <td className="px-4 py-4 whitespace-nowrap">
                                                        <div className="text-sm text-gray-900">
                                                            {request.payment_method}
                                                        </div>
                                                        {request.transaction_id && (
                                                            <div className="text-sm text-gray-500">
                                                                Transaction ID: {request.transaction_id}
                                                            </div>
                                                        )}
                                                        {request.proof_of_payment && (
                                                            <div className="text-sm text-blue-600 hover:text-blue-800">
                                                                <a href={request.proof_of_payment} target="_blank" rel="noopener noreferrer">
                                                                    View Proof
                                                                </a>
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-4 whitespace-nowrap">
                                                        <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                            ${request.status === 'approved' 
                                                                ? 'bg-green-100 text-green-800' 
                                                                : request.status === 'rejected'
                                                                ? 'bg-red-100 text-red-800'
                                                                : 'bg-yellow-100 text-yellow-800'
                                                            }`}>
                                                            {request.status.charAt(0).toUpperCase() + request.status.slice(1)}
                                                        </span>
                                                        {request.approved_date && (
                                                            <div className="text-xs text-gray-500 mt-1">
                                                                {formatDate(request.approved_date)}
                                                            </div>
                                                        )}
                                                        {request.approved_by && (
                                                            <div className="text-xs text-gray-500">
                                                                by {request.approved_by}
                                                            </div>
                                                        )}
                                                    </td>
                                                    <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                                        {formatDate(request.created_at)}
                                                    </td>
                                                    <td className="px-4 py-4 whitespace-nowrap text-sm font-medium space-x-2">
                                                        {request.status === 'pending' && (
                                                            <>
                                                                <button
                                                                    onClick={() => handleAction(request.id, 'accept')}
                                                                    disabled={processing === request.id}
                                                                    className="bg-green-500 hover:bg-green-600 text-white px-3 py-1.5 rounded-md text-sm disabled:opacity-50"
                                                                >
                                                                    {processing === request.id ? 'Processing...' : 'Approve'}
                                                                </button>
                                                                <button
                                                                    onClick={() => handleAction(request.id, 'reject')}
                                                                    disabled={processing === request.id}
                                                                    className="bg-red-500 hover:bg-red-600 text-white px-3 py-1.5 rounded-md text-sm disabled:opacity-50"
                                                                >
                                                                    {processing === request.id ? 'Processing...' : 'Reject'}
                                                                </button>
                                                            </>
                                                        )}
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>

                                {creditRequests.data.length === 0 && (
                                    <div className="text-center py-4 text-gray-500">
                                        No pending credit requests
                                    </div>
                                )}

                                {creditRequests.last_page > 1 && (
                                    <div className="mt-4 flex justify-between items-center">
                                        <div className="text-sm text-gray-700">
                                            Showing {creditRequests.from} to {creditRequests.to} of {creditRequests.total} results
                                        </div>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Client Credits Tab */}
                    {activeTab === 'clients' && (
                        <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                            <div className="p-6">
                                <h3 className="text-lg font-medium text-gray-900 mb-4">Client Credit Overview</h3>
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Client
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Current Credits
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Pending Credits
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Total Purchased
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Total Spent
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Last Activity
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="bg-white divide-y divide-gray-200">
                                            {clientsWithCredits.map((client) => {
                                                const recentPurchases = client.recent_history.filter(h => h.type === 'purchase').length;
                                                const recentSpent = client.recent_history.filter(h => h.type === 'spent').length;
                                                const totalRecent = client.recent_history.length;
                                                
                                                return (
                                                    <tr key={client.id}>
                                                        <td className="px-4 py-4 whitespace-nowrap">
                                                            <div className="text-sm text-gray-900 font-medium">
                                                                {client.name}
                                                            </div>
                                                            <div className="text-sm text-gray-500">
                                                                {client.client_identifier}
                                                            </div>
                                                            <div className="text-sm text-gray-500">
                                                                {client.email}
                                                            </div>
                                                            <span className={`px-2 inline-flex text-xs leading-5 font-semibold rounded-full 
                                                                ${client.active ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800'}`}>
                                                                {client.active ? 'Active' : 'Inactive'}
                                                            </span>
                                                        </td>
                                                        <td className="px-4 py-4 whitespace-nowrap">
                                                            <div className="text-sm text-gray-900 font-medium">
                                                                {formatCredits(client.current_credits)} Credits
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-4 whitespace-nowrap">
                                                            <div className="text-sm text-gray-900">
                                                                {formatCredits(client.pending_credits)} Credits
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-4 whitespace-nowrap">
                                                            <div className="text-sm text-gray-900">
                                                                {formatCredits(client.total_purchased)} Credits
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-4 whitespace-nowrap">
                                                            <div className="text-sm text-gray-900">
                                                                {formatCredits(client.total_spent)} Credits
                                                            </div>
                                                        </td>
                                                        <td className="px-4 py-4 whitespace-nowrap text-sm text-gray-500">
                                                            {client.last_activity ? formatDate(client.last_activity) : 'No activity'}
                                                        </td>
                                                        <td className="px-4 py-4 whitespace-nowrap text-sm font-medium">
                                                            <button
                                                                onClick={() => handleViewHistory(client)}
                                                                className="bg-blue-500 hover:bg-blue-600 text-white px-3 py-1.5 rounded-md text-sm"
                                                            >
                                                                View History
                                                            </button>
                                                        </td>
                                                    </tr>
                                                );
                                            })}
                                        </tbody>
                                    </table>
                                </div>

                                {clientsWithCredits.length === 0 && (
                                    <div className="text-center py-4 text-gray-500">
                                        No clients found
                                    </div>
                                )}
                            </div>
                        </div>
                    )}
                </div>
            </div>

            {/* History Modal */}
            {showHistoryModal && selectedClient && (
                <div className="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full z-50">
                    <div className="relative top-20 mx-auto p-5 border w-11/12 md:w-3/4 lg:w-1/2 shadow-lg rounded-md bg-white">
                        <div className="mt-3">
                            <div className="flex justify-between items-center mb-4">
                                <h3 className="text-lg font-medium text-gray-900">
                                    Credit History - {selectedClient.name}
                                </h3>
                                <button
                                    onClick={closeHistoryModal}
                                    className="text-gray-400 hover:text-gray-600"
                                >
                                    <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M6 18L18 6M6 6l12 12" />
                                    </svg>
                                </button>
                            </div>
                            
                            {/* Client Summary */}
                            <div className="bg-gray-50 p-4 rounded-lg mb-4">
                                <div className="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                                    <div>
                                        <div className="text-gray-500">Current Credits</div>
                                        <div className="font-medium">{formatCredits(selectedClient.current_credits)}</div>
                                    </div>
                                    <div>
                                        <div className="text-gray-500">Pending Credits</div>
                                        <div className="font-medium">{formatCredits(selectedClient.pending_credits)}</div>
                                    </div>
                                    <div>
                                        <div className="text-gray-500">Total Purchased</div>
                                        <div className="font-medium">{formatCredits(selectedClient.total_purchased)}</div>
                                    </div>
                                    <div>
                                        <div className="text-gray-500">Total Spent</div>
                                        <div className="font-medium">{formatCredits(selectedClient.total_spent)}</div>
                                    </div>
                                </div>
                            </div>

                            {/* Detailed History */}
                            <div className="max-h-96 overflow-y-auto">
                                <div className="flex justify-between items-center mb-3">
                                    <h4 className="text-md font-medium text-gray-900">Recent Transactions</h4>
                                    {totalItems > 0 && (
                                        <span className="text-sm text-gray-500">
                                            Showing {((currentPage - 1) * itemsPerPage) + 1} to {Math.min(currentPage * itemsPerPage, totalItems)} of {totalItems} transactions
                                        </span>
                                    )}
                                </div>
                                
                                {selectedClient.recent_history && Array.isArray(selectedClient.recent_history) && selectedClient.recent_history.length > 0 ? (
                                    <div className="space-y-3">
                                        {paginatedHistory.map((history, index) => (
                                            <div key={index} className="border rounded-lg p-3">
                                                <div className="flex justify-between items-start">
                                                    <div className="flex-1">
                                                        <div className="flex items-center space-x-2 mb-1">
                                                            <span className={`px-2 py-1 text-xs rounded-full ${
                                                                history.type === 'purchase' 
                                                                    ? 'bg-blue-100 text-blue-800' 
                                                                    : 'bg-orange-100 text-orange-800'
                                                            }`}>
                                                                {history.type === 'purchase' ? 'Purchase' : 'Spent'}
                                                            </span>
                                                            <span className="text-sm font-medium">
                                                                {history.type === 'purchase' ? '+' : '-'}{formatCredits(history.amount)} Credits
                                                            </span>
                                                        </div>
                                                        <div className="text-sm text-gray-600">
                                                            {history.details}
                                                        </div>
                                                        <div className="text-xs text-gray-500 mt-1">
                                                            {formatDate(history.date)}
                                                        </div>
                                                    </div>
                                                    <div className="text-right">
                                                        <span className={`text-xs px-2 py-1 rounded-full ${
                                                            history.status === 'approved' || history.status === 'spent'
                                                                ? 'bg-green-100 text-green-800'
                                                                : history.status === 'pending'
                                                                ? 'bg-yellow-100 text-yellow-800'
                                                                : 'bg-red-100 text-red-800'
                                                        }`}>
                                                            {history.status.charAt(0).toUpperCase() + history.status.slice(1)}
                                                        </span>
                                                    </div>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                ) : (
                                    <div className="text-center py-8 text-gray-500">
                                        No transaction history available
                                    </div>
                                )}

                                {/* Enhanced Pagination */}
                                {selectedClient.recent_history && Array.isArray(selectedClient.recent_history) && selectedClient.recent_history.length > 0 && totalPages > 1 && (
                                    <div className="mt-6 border-t pt-4">
                                        <div className="flex justify-between items-center">
                                            <div className="text-sm text-gray-700">
                                                Page {currentPage} of {totalPages}
                                            </div>
                                            <div className="flex items-center space-x-2">
                                                <button
                                                    onClick={() => goToPage(1)}
                                                    disabled={currentPage === 1}
                                                    className="bg-gray-200 hover:bg-gray-300 text-gray-800 px-2 py-1 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                                    title="First Page"
                                                >
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M11 19l-7-7 7-7m8 14l-7-7 7-7" />
                                                    </svg>
                                                </button>
                                                <button
                                                    onClick={goToPreviousPage}
                                                    disabled={currentPage === 1}
                                                    className="bg-gray-200 hover:bg-gray-300 text-gray-800 px-2 py-1 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                                    title="Previous Page"
                                                >
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 19l-7-7 7-7" />
                                                    </svg>
                                                </button>
                                                
                                                {/* Page Numbers */}
                                                <div className="flex items-center space-x-1">
                                                    {Array.from({ length: Math.min(5, totalPages) }, (_, i) => {
                                                        let pageNum;
                                                        if (totalPages <= 5) {
                                                            pageNum = i + 1;
                                                        } else if (currentPage <= 3) {
                                                            pageNum = i + 1;
                                                        } else if (currentPage >= totalPages - 2) {
                                                            pageNum = totalPages - 4 + i;
                                                        } else {
                                                            pageNum = currentPage - 2 + i;
                                                        }
                                                        
                                                        return (
                                                            <button
                                                                key={pageNum}
                                                                onClick={() => goToPage(pageNum)}
                                                                className={`px-3 py-1 rounded text-sm ${
                                                                    currentPage === pageNum
                                                                        ? 'bg-blue-500 text-white'
                                                                        : 'bg-gray-200 hover:bg-gray-300 text-gray-800'
                                                                }`}
                                                            >
                                                                {pageNum}
                                                            </button>
                                                        );
                                                    })}
                                                </div>
                                                
                                                <button
                                                    onClick={goToNextPage}
                                                    disabled={currentPage === totalPages}
                                                    className="bg-gray-200 hover:bg-gray-300 text-gray-800 px-2 py-1 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                                    title="Next Page"
                                                >
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                                                    </svg>
                                                </button>
                                                <button
                                                    onClick={() => goToPage(totalPages)}
                                                    disabled={currentPage === totalPages}
                                                    className="bg-gray-200 hover:bg-gray-300 text-gray-800 px-2 py-1 rounded text-sm disabled:opacity-50 disabled:cursor-not-allowed"
                                                    title="Last Page"
                                                >
                                                    <svg className="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M13 5l7 7-7 7" />
                                                    </svg>
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                )}
                            </div>

                            <div className="mt-6 flex justify-end">
                                <button
                                    onClick={closeHistoryModal}
                                    className="bg-gray-500 hover:bg-gray-600 text-white px-4 py-2 rounded-md text-sm"
                                >
                                    Close
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            )}
        </AuthenticatedLayout>
    );
}
