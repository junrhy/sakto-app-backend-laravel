import { Head } from '@inertiajs/react';
import { PageProps } from '@/types';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import { useState } from 'react';
import { router } from '@inertiajs/react';

interface CreditHistory {
    id: number;
    client_identifier: string;
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

interface Props extends PageProps {
    creditRequests: {
        data: CreditHistory[];
        current_page: number;
        last_page: number;
        from: number;
        to: number;
        total: number;
    };
}

export default function Index({ auth, creditRequests }: Props) {
    const [processing, setProcessing] = useState<number | null>(null);

    const handleAction = async (id: number, action: 'accept' | 'reject') => {
        try {
            setProcessing(id);
            router.post(`/admin/credits/${action}-request/${id}`, {}, {
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

    return (
        <AuthenticatedLayout
            header={<h2 className="font-semibold text-xl text-gray-800 leading-tight">Manage Credit Requests</h2>}
        >
            <Head title="Credit Requests" />

            <div className="py-12">
                <div className="w-full mx-auto px-4 sm:px-6 lg:px-8">
                    <div className="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                        <div className="p-6">
                            <div className="overflow-x-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-3 text-left text-xs font-medium text-gray-500 uppercase tracking-wider">
                                                Client Identifier
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
                                                    {request.client_identifier}
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
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
