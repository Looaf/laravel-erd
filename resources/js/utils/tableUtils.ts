import { Column, Relationship, Table } from "../types/erd";

export const getColumnIcon = (column: Column): string => {
    if (column.primary) return '🔑';
    if (column.unique) return '🔒';
    if (!column.nullable) return '❗';
    return '';
  };

export const getColumnTypeColor = (type: string): string => {
const typeColors: Record<string, string> = {
    'bigint': 'text-blue-600',
    'int': 'text-blue-600',
    'integer': 'text-blue-600',
    'varchar': 'text-green-600',
    'string': 'text-green-600',
    'text': 'text-green-600',
    'boolean': 'text-purple-600',
    'datetime': 'text-orange-600',
    'timestamp': 'text-orange-600',
    'date': 'text-orange-600',
    'json': 'text-red-600',
    'decimal': 'text-yellow-600',
    'float': 'text-yellow-600',
};

return typeColors[type.toLowerCase()] || 'text-gray-600';
};

export const getRelationshipSummary = (relationships: Relationship[], table: Table): string => {
if (relationships.length === 0) return '';

const relationshipTypes = relationships.reduce((acc, rel) => {
    const type = rel.source === table.id ? rel.type : 'inverse';
    acc[type] = (acc[type] || 0) + 1;
    return acc;
}, {} as Record<string, number>);

const summary = Object.entries(relationshipTypes)
    .map(([type, count]) => `${count} ${type}`)
    .join(', ');

return `${relationships.length} relationship${relationships.length !== 1 ? 's' : ''}: ${summary}`;
};