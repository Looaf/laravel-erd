import React, { useState } from 'react';
import { useStore } from 'reactflow';
import { Table, Relationship, Column } from '../types/erd';

interface TableNodeProps {
  table: Table;
  relationships: Relationship[];
}

const TableNode: React.FC<TableNodeProps> = ({ table, relationships }) => {
  const [isHovered, setIsHovered] = useState<boolean>(false);
  const [isDragging, setIsDragging] = useState<boolean>(false);
  
  // Get dragging state from React Flow store
  const nodeInternals = useStore((state) => state.nodeInternals);
  const currentNode = nodeInternals.get(table.id);
  const isBeingDragged = currentNode?.dragging || false;

  const getColumnIcon = (column: Column): string => {
    if (column.primary) return '🔑';
    if (column.unique) return '🔒';
    if (!column.nullable) return '❗';
    return '';
  };

  const getColumnTypeColor = (type: string): string => {
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

  const getRelationshipSummary = (): string => {
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

  return (
    <div
      className={`erd-table bg-white border border-gray-300 rounded-lg shadow-sm min-w-[280px] max-w-[320px] transition-all duration-200 ${
        isBeingDragged ? 'shadow-xl border-blue-500 scale-105 rotate-1' : 
        isHovered ? 'shadow-lg border-blue-400' : ''
      }`}
      onMouseEnter={() => setIsHovered(true)}
      onMouseLeave={() => setIsHovered(false)}
    >
      {/* Table Header */}
      <div className="bg-blue-600 text-white px-4 py-3 rounded-t-lg flex items-center justify-between cursor-grab active:cursor-grabbing">
        <div className="flex items-center space-x-2 flex-1 min-w-0">
          {/* Drag handle indicator */}
          <svg className="w-4 h-4 opacity-70 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
            <path d="M10 6a2 2 0 110-4 2 2 0 010 4zM10 12a2 2 0 110-4 2 2 0 010 4zM10 18a2 2 0 110-4 2 2 0 010 4z"/>
          </svg>
          <h3 className="font-semibold text-base truncate">{table.name}</h3>
        </div>
        <div className="text-xs opacity-90 flex-shrink-0 ml-2">
          {table.columns.length}
        </div>
      </div>

      {/* Columns List */}
      <div className="overflow-y-auto">
        {table.columns.map((column, index) => (
          <div
            key={`${table.id}-${column.name}`}
            className={`px-4 py-2 border-b border-gray-100 last:border-b-0 flex items-center justify-between group hover:bg-gray-50 ${
              column.primary ? 'bg-blue-50 border-l-4 border-l-blue-500' : ''
            }`}
          >
            <div className="flex items-center space-x-2 flex-1 min-w-0">
              <span className="text-xs w-4 text-center" title={
                column.primary ? 'Primary Key' : 
                column.unique ? 'Unique' : 
                !column.nullable ? 'Not Nullable' : ''
              }>
                {getColumnIcon(column)}
              </span>
              <span className={`font-medium truncate text-sm ${
                column.primary ? 'text-blue-700' : 'text-gray-900'
              }`}>
                {column.name}
              </span>
            </div>
            <div className="flex items-center space-x-1 text-xs flex-shrink-0 ml-2">
              <span className={`${getColumnTypeColor(column.type)} font-mono`}>
                {column.type}
              </span>
              {column.nullable && (
                <span className="text-gray-400" title="Nullable">?</span>
              )}
            </div>
          </div>
        ))}
      </div>

      {/* Relationships Summary */}
      {relationships.length > 0 && (
        <div className="px-4 py-2 bg-gray-50 border-t border-gray-200 rounded-b-lg">
          <div className="text-xs text-gray-600" title={getRelationshipSummary()}>
            <span className="inline-flex items-center">
              <svg className="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fillRule="evenodd" d="M12.586 4.586a2 2 0 112.828 2.828l-3 3a2 2 0 01-2.828 0 1 1 0 00-1.414 1.414 4 4 0 005.656 0l3-3a4 4 0 00-5.656-5.656l-1.5 1.5a1 1 0 101.414 1.414l1.5-1.5zm-5 5a2 2 0 012.828 0 1 1 0 101.414-1.414 4 4 0 00-5.656 0l-3 3a4 4 0 105.656 5.656l1.5-1.5a1 1 0 10-1.414-1.414l-1.5 1.5a2 2 0 11-2.828-2.828l3-3z" clipRule="evenodd" />
              </svg>
              {relationships.length} rel{relationships.length !== 1 ? 's' : ''}
            </span>
          </div>
        </div>
      )}
    </div>
  );
};

export default TableNode;